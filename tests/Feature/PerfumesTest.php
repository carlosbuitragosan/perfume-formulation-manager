<?php

use App\Models\Perfume;
use App\Models\User;

// -----------------------
// Create user for all tests
// -----------------------
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    [$this->blend, $this->blendVersion] = makeBlendWithVersion($this->user, 'Test Blend');
});

it('allows navigating to the perfume creation form from a blend version', function () {
    // Get HTML for blends show page and filter for version div
    [$response, $crawler] = getPageCrawler($this->user, route('blends.show', $this->blend));
    $versionDiv = $crawler->filter("div#version-{$this->blendVersion->id}");

    // Assert "Create Perfume" button exists
    expect($versionDiv->selectLink('Create Perfume')->count())->toBe(1);
    expect($versionDiv->selectLink('Create Perfume')->link()->getUri())->toBe(route('perfumes.create', $this->blendVersion));

    // Assert button links to perfume creation page for version
    getAs($this->user, route('perfumes.create', $this->blendVersion))
        ->assertOk();
});

it('displays perfume size and concentration inputs when creating a perfume', function () {
    // Get perfume creation page for version
    [, $crawler] = getPageCrawler($this->user, route('perfumes.create', $this->blendVersion));

    // Assert size and concentration inputs are present
    expect($crawler->filter('input[name="size"]')->count())->toBe(1);
    expect($crawler->filter('input[name="concentration"]')->count())->toBe(1);
});

it('returns to the originating blend version when cancelling perfume creation', function () {
    // Get perfume creation page for version
    [, $crawler] = getPageCrawler($this->user, route('perfumes.create', $this->blendVersion));

    // Assert cancel link contains correct URL with fragment for version
    expect($crawler->selectLink('CANCEL')->link()->getUri())->toBe(route('blends.show', $this->blend).'#version-'.$this->blendVersion->id);
});

it('creates a perfume from a blend version and redirects to the perfume page', function () {
    // Post to perfume store route with form data
    $response = postAs($this->user, route('perfumes.store', $this->blendVersion), [
        'name' => 'Test Perfume',
        'size' => 50,
        'concentration' => 20,
    ]);

    // Assert perfume was created in database with correct attributes
    $this->assertDatabaseHas('perfumes', [
        'blend_version_id' => $this->blendVersion->id,
        'name' => 'Test Perfume',
    ]);

    $perfume = Perfume::where('name', 'Test Perfume')->first();

    $this->assertDatabaseHas('perfume_versions', [
        'perfume_id' => $perfume->id,
        'size' => 50,
        'concentration' => 20,
    ]);

    // Assert response redirects to perfumes show page
    $response->assertRedirect(route('perfumes.show', $perfume));

    $this->get(route('perfumes.show', $perfume))
        ->assertOk();
});

it('displays an error when creating a perfume from a version with missing bottle or density data', function () {
    // Add ingredients to version without bottle or density data
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'neroli']);
    $neroliBottle = makeBottle($neroli, ['density' => null]);
    addIngredient($this->blendVersion, $lavender);
    addIngredient($this->blendVersion, $neroli, $neroliBottle->id);

    // Attempt GET request to perfume creation
    $response = getAs($this->user, route('perfumes.create', $this->blendVersion));

    // Assert redirect back to blends show
    $response->assertRedirect(route('blends.show', $this->blend).'#version-'.$this->blendVersion->id);

    // Assert session error message
    $response->assertSessionHas(
        'alerts',
        ['Lavender is missing a bottle.', 'Neroli is missing density.']
    );

    // Assert message is displayed on blends show page
    [, $crawler] = getPageCrawler($this->user, route('blends.show', $this->blend));
    expect($crawler->filter('div#version-'.$this->blendVersion->id)->text())->toContain('Lavender is missing a bottle. Neroli is missing density.');
});

it('displays the perfume details on the perfume show page', function () {
    // Create materials
    $lavender = makeMaterial();
    $rose = makeMaterial(['name' => 'Rose']);
    $neroli = makeMaterial(['name' => 'Neroli']);

    // Create bottles with density
    $lavenderBottle = makeBottle($lavender, ['density' => 0.9]);
    $roseBottle = makeBottle($rose, ['density' => 0.8]);
    $neroliBottle = makeBottle($neroli, ['density' => 0.7]);

    // Add ingredients to version
    addIngredient($this->blendVersion, $lavender, $lavenderBottle->id, 5, 1);
    addIngredient($this->blendVersion, $rose, $roseBottle->id, 6, 25);
    addIngredient($this->blendVersion, $neroli, $neroliBottle->id, 7, 1);

    // Create perfume from blend version
    $perfume = $this->blendVersion->perfumes()->create([
        'name' => 'Test Perfume',
    ]);

    $perfumeVersion = $perfume->versions()->create([
        'size' => 50,
        'concentration' => 20,

    ]);
    // Assert perfume show page displays perfume details
    [$response, $crawler] = getPageCrawler($this->user, route('perfumes.show', $perfume));
    $perfumeHeader = $crawler->filter('div#header');
    $perfumeVersionContainer = $crawler->filter('div#version-'.$perfumeVersion->id);

    $lavenderContainer = $perfumeVersionContainer->filter('tr[data-material-id="'.$lavender->id.'"]');
    $roseContainer = $perfumeVersionContainer->filter('tr[data-material-id="'.$rose->id.'"]');
    $neroliContainer = $perfumeVersionContainer->filter('tr[data-material-id="'.$neroli->id.'"]');

    // Assert header displays perfume name
    expect($perfumeHeader->text())->toContain('Test Perfume');

    // Assert version container displays size and concentration
    expect($perfumeVersionContainer->text())->toContain('50 ml');
    expect($perfumeVersionContainer->text())->toContain('20%');

    // Lavender
    expect($lavenderContainer->filter('td[data-col="material"]')->text())->toBe('Lavender');
    expect($lavenderContainer->filter('td[data-col="weight"]')->text())->toBe('0.278 g');
    expect($lavenderContainer->filter('td[data-col="percentage"]')->text())->toBe('0.62%');

    // Rose
    expect($roseContainer->filter('td[data-col="material"]')->text())->toBe('Rose');
    expect($roseContainer->filter('td[data-col="weight"]')->text())->toBe('7.407 g');
    expect($roseContainer->filter('td[data-col="percentage"]')->text())->toBe('18.52%');

    // Neroli
    expect($neroliContainer->filter('td[data-col="material"]')->text())->toBe('Neroli');
    expect($neroliContainer->filter('td[data-col="weight"]')->text())->toBe('0.302 g');
    expect($neroliContainer->filter('td[data-col="percentage"]')->text())->toBe('0.86%');
});

test('perfume displays ingredients according to pyramid values', function () {
    // Create materials
    $topHeartMaterial = makeMaterial(['name' => 'top heart', 'pyramid' => ['top', 'heart']]);
    $topMaterial = makeMaterial(['name' => 'top', 'pyramid' => ['top']]);
    $heartBaseMaterial = makeMaterial(['name' => 'heart base', 'pyramid' => ['heart', 'base']]);
    $heartMaterial = makeMaterial(['name' => 'heart', 'pyramid' => ['heart']]);
    $baseMaterial = makeMaterial(['name' => 'base', 'pyramid' => ['base']]);
    $topHeartBaseMaterial = makeMaterial(['name' => 'top heart base', 'pyramid' => ['top', 'heart', 'base']]);

    //  Create bottles with density
    $topHeartBottle = makeBottle($topHeartMaterial);
    $topBottle = makeBottle($topMaterial);
    $heartBaseBottle = makeBottle($heartBaseMaterial);
    $heartBottle = makeBottle($heartMaterial);
    $baseBottle = makeBottle($baseMaterial);
    $topHeartBaseBottle = makeBottle($topHeartBaseMaterial);

    // Add ingredients to version
    addIngredient($this->blendVersion, $topHeartMaterial, $topHeartBottle->id);
    addIngredient($this->blendVersion, $topMaterial, $topBottle->id);
    addIngredient($this->blendVersion, $heartBaseMaterial, $heartBaseBottle->id);
    addIngredient($this->blendVersion, $heartMaterial, $heartBottle->id);
    addIngredient($this->blendVersion, $baseMaterial, $baseBottle->id);
    addIngredient($this->blendVersion, $topHeartBaseMaterial, $topHeartBaseBottle->id);

    // Create perfume from version
    $perfume = $this->blendVersion->perfumes()->create([
        'name' => 'Test Perfume',
    ]);

    $perfumeVersion = $perfume->versions()->create([
        'size' => 50,
        'concentration' => 20,
    ]);

    // Assert perfume show page displays pyramid values for each ingredient
    [, $crawler] = getPageCrawler($this->user, route('perfumes.show', $perfume));

    $ingredientsTable = $crawler->filter('div#version-'.$perfume->id.' tbody');

    // collection of table rows in the exact order they are displayed on the page
    $rows = $ingredientsTable->filter('tr');

    // Assert ingredients are listed in order of pyramid values (e.g. top to base note)
    $expectedOrder = [
        $topMaterial->name,
        $topHeartMaterial->name,
        $heartMaterial->name,
        $heartBaseMaterial->name,
        $topHeartBaseMaterial->name,
        $baseMaterial->name,
    ];

    foreach ($expectedOrder as $index => $name) {
        expect($rows->eq($index)->text())->toContain($name);
    }
});

it('shows a link to the perfume index in both mobile and desktop navigation', function () {
    [, $crawler] = getPageCrawler($this->user, route('materials.index'));

    expect($crawler->selectLink('Perfumes')->count())->toBe(2);

    foreach ($crawler->selectLink('Perfumes') as $link) {
        expect($link->getAttribute('href'))->toBe(route('perfumes.index'));
    }
});

it('displays a list of perfumes on the perfume index page', function () {
    // Create perfumes
    $perfume1 = $this->blendVersion->perfumes()->create(['name' => 'Perfume 1']);
    $perfume2 = $this->blendVersion->perfumes()->create(['name' => 'Perfume 2']);

    // Assert perfume index page displays created perfumes
    getAs($this->user, route('perfumes.index'))
        ->assertOk()
        ->assertSee('Perfume 1')
        ->assertSee('Perfume 2');
});
