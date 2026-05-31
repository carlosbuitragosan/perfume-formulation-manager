<?php

use App\Models\Perfume;
use App\Models\User;

// -----------------------
// Create user for all tests
// -----------------------
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    [$this->blend, $this->version] = makeBlendWithVersion($this->user, 'Test Blend');
});

it('allows navigating to the perfume creation form from a blend version', function () {
    // Get HTML for blends show page and filter for version div
    [$response, $crawler] = getPageCrawler($this->user, route('blends.show', $this->blend));
    $versionDiv = $crawler->filter("div#version-{$this->version->id}");

    // Assert "Create Perfume" button exists
    expect($versionDiv->selectLink('Create Perfume')->count())->toBe(1);
    expect($versionDiv->selectLink('Create Perfume')->link()->getUri())->toBe(route('versions.perfumes.create', $this->version));

    // Assert button links to perfume creation page for version
    getAs($this->user, route('versions.perfumes.create', $this->version))
        ->assertOk();
});

it('displays perfume size, concentration, and carrier type inputs when creating a perfume', function () {
    // Get perfume creation page for version
    [, $crawler] = getPageCrawler($this->user, route('versions.perfumes.create', $this->version));

    // Assert size and concentration inputs are present
    expect($crawler->filter('input[name="size"]')->count())->toBe(1);
    expect($crawler->filter('input[name="concentration"]')->count())->toBe(1);
    expect($crawler->filter('select[name="carrier_type"]')->count())->toBe(1);
});

it('returns to the originating blend version when cancelling perfume creation', function () {
    // Get perfume creation page for version
    [, $crawler] = getPageCrawler($this->user, route('versions.perfumes.create', $this->version));

    // Assert cancel link contains correct URL with fragment for version
    expect($crawler->selectLink('CANCEL')->link()->getUri())->toBe(route('blends.show', $this->blend).'#version-'.$this->version->id);
});

it('creates a perfume from a blend version and redirects to the perfume page', function () {
    // Post to perfume store route with form data
    $response = postAs($this->user, route('versions.perfumes.store', $this->version), [
        'name' => 'Test Perfume',
        'size' => 50,
        'concentration' => 20,
        'carrier_type' => 'alcohol',
    ]);

    // Assert perfume was created in database with correct attributes
    $this->assertDatabaseHas('perfumes', [
        'blend_version_id' => $this->version->id,
        'name' => 'Test Perfume',
        'size' => 50,
        'concentration' => 20,
        'carrier_type' => 'alcohol',
    ]);

    // Assert response redirects to perfume index page
    $perfume = Perfume::where('blend_version_id', $this->version->id)->first();
    $response->assertRedirect(route('perfumes.show', $perfume));

    $this->get(route('perfumes.show', $perfume))
        ->assertOk();
});

it('Supports only alcohol carrier type', function () {
    // Post to perfume store route with unupported carrier type
    $response = $this
        ->from(route('versions.perfumes.create', $this->version))
        ->post(route('versions.perfumes.store', $this->version), [
            'name' => 'Test Perfume',
            'size' => 50,
            'concentration' => 20,
            'carrier_type' => 'oil',
        ]);

    // Assert perfume was not created in database
    $this->assertDatabaseMissing('perfumes', [
        'blend_version_id' => $this->version->id,
        'name' => 'Test Perfume',
        'size' => 50,
        'concentration' => 20,
        'carrier_type' => 'oil',
    ]);

    // Assert response redirects back to perfume creation page
    $response->assertRedirect(route('versions.perfumes.create', $this->version));

    // Assert validation error for carrier type
    $response->assertSessionHasErrors(['carrier_type' => 'Only alcohol perfumes are currently supported.']);
});

it('displays an error when creating a perfume from a version with missing bottle or density data', function () {
    // Add ingredients to version without bottle or density data
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'neroli']);
    $neroliBottle = makeBottle($neroli, ['density' => null]);
    addIngredient($this->version, $lavender);
    addIngredient($this->version, $neroli, $neroliBottle->id);

    // Attempt GET request to perfume creation
    $response = getAs($this->user, route('versions.perfumes.create', $this->version));

    // Assert redirect back to blends show
    $response->assertRedirect(route('blends.show', $this->blend).'#version-'.$this->version->id);

    // Assert session error message
    $response->assertSessionHas(
        'alerts',
        ['Lavender is missing a bottle.', 'Neroli is missing density.']
    );

    // Assert message is displayed on blends show page
    [, $crawler] = getPageCrawler($this->user, route('blends.show', $this->blend));
    expect($crawler->filter('div#version-'.$this->version->id)->text())->toContain('Lavender is missing a bottle. Neroli is missing density.');
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
    addIngredient($this->version, $lavender, $lavenderBottle->id, 5, 1);
    addIngredient($this->version, $rose, $roseBottle->id, 6, 25);
    addIngredient($this->version, $neroli, $neroliBottle->id, 7, 1);

    // Create perfume from version
    $perfume = $this->version->perfumes()->create([
        'name' => 'Test Perfume',
        'size' => 50,
        'concentration' => 20,
        'carrier_type' => 'alcohol',
    ]);

    // Assert perfume show page displays perfume details
    [$response, $crawler] = getPageCrawler($this->user, route('perfumes.show', $perfume));
    $lavenderContainer = $crawler->filter('tr[data-material-id="'.$lavender->id.'"]');
    $roseContainer = $crawler->filter('tr[data-material-id="'.$rose->id.'"]');
    $neroliContainer = $crawler->filter('tr[data-material-id="'.$neroli->id.'"]');

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
    addIngredient($this->version, $topHeartMaterial, $topHeartBottle->id);
    addIngredient($this->version, $topMaterial, $topBottle->id);
    addIngredient($this->version, $heartBaseMaterial, $heartBaseBottle->id);
    addIngredient($this->version, $heartMaterial, $heartBottle->id);
    addIngredient($this->version, $baseMaterial, $baseBottle->id);
    addIngredient($this->version, $topHeartBaseMaterial, $topHeartBaseBottle->id);

    // Create perfume from version
    $perfume = $this->version->perfumes()->create([
        'name' => 'Test Perfume',
        'size' => 50,
        'concentration' => 20,
        'carrier_type' => 'alcohol',
    ]);

    // Assert perfume show page displays pyramid values for each ingredient
    [, $crawler] = getPageCrawler($this->user, route('perfumes.show', $perfume));

    $ingredientsTable = $crawler->filter('div#perfume-'.$perfume->id.' tbody');

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
