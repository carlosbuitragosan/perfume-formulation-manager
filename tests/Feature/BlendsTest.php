<?php

use App\Models\Blend;
use App\Models\Bottle;
use App\Models\User;

// Create user
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('Dashboard shows a "create blend" link that leads to the blend creation page', function () {
    [, $crawler] = getPageCrawler($this->user, route('dashboard'));
    $link = $crawler->selectLink('Create Blend');

    expect($link->count())->toBe(1);
    expect($link->link()->getUri())->toContain('/blends/create');
});

it('shows the create blend form', function () {
    [, $crawler] = getPageCrawler($this->user, route('blends.create'));
    $form = $crawler->filter('form#create-blend-form');

    expect($form->count())->toBe(1);
    expect($form->filter('select[name="materials[0][material_id]"]')->count())->toBe(1);
    expect($form->filter('input[name="name"]')->count())->toBe(1);
    expect($form->filter('input[name="materials[0][drops]"]')->count())->toBe(1);
    expect($form->filter('select[name="materials[0][dilution]"]')->count())->toBe(1);
    expect($form->filter('button[type="submit"]')->text())->toContain('SAVE');
});

it('creates a blend and redirects to its page', function () {
    $lavender = makeMaterial();
    $galbanum = makeMaterial(['name' => 'Galbanum']);
    $payload = blendPayload('Sunshine', [
        ingredient($lavender),
        ingredient($galbanum),
    ]);

    $response = postAs($this->user, route('blends.store'), $payload);

    $blend = Blend::where('user_id', $this->user->id)
        ->where('name', 'Sunshine')
        ->firstOrFail();

    // Redirects to blend
    $response->assertRedirect(route('blends.show', $blend));
    // Blend is created
    $this->assertDatabaseHas('blends', [
        'user_id' => $this->user->id,
        'name' => 'Sunshine',
    ]);
});

it('shows existing blend on the dashboard', function () {
    $blend = Blend::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Sunshine',
    ]);
    [, $crawler] = getPageCrawler($this->user, route('dashboard'));
    $link = $crawler->selectLink($blend->name);

    expect($link->count())->toBe(1);
    expect($link->text())->toContain('Sunshine');
});

it('shows blend version with ingredients and pure % breakdown', function () {
    $lavender = makeMaterial();
    $galbanum = makeMaterial(['name' => 'Galbanum']);
    [$blend, $version] = makeBlendWithVersion($this->user, 'Sunshine');
    addIngredient($version, $lavender);
    addIngredient($version, $galbanum);

    [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));

    // Blend name
    expect($crawler->filter('header')->text())->toContain($blend->name);
    // Blend version
    expect($crawler->filter('div[data-testid="blend-version"][data-version="1.0"]')->count())->toBe(1);
    // Lavender
    $lavenderRow = $crawler->filter('tr[data-testid="blend-ingredient-row"][data-material-id="'.$lavender->id.'"]');
    expect($lavenderRow->count())->toBe(1);
    expect($lavenderRow->filter('[data-col="material"]')->text())->toBe($lavender->name);
    expect($lavenderRow->filter('[data-col="drops"]')->text())->toBe('4');
    expect($lavenderRow->filter('[data-col="dilution"]')->text())->toBe('25%');
    expect($lavenderRow->filter('[data-col="pure_pct"]')->text())->toBe('50.00%');
    // Galbanum
    $galbanumRow = $crawler->filter('tr[data-testid="blend-ingredient-row"][data-material-id="'.$galbanum->id.'"]');
    expect($galbanumRow->count())->toBe(1);
    expect($galbanumRow->filter('[data-col="material"]')->text())->toBe($galbanum->name);
    expect($galbanumRow->filter('[data-col="drops"]')->text())->toBe('4');
    expect($galbanumRow->filter('[data-col="dilution"]')->text())->toBe('25%');
    expect($galbanumRow->filter('[data-col="pure_pct"]')->text())->toBe('50.00%');
});

test('create form shows only one ingredient row with the expected inputs', function () {
    [, $crawler] = getPageCrawler($this->user, route('blends.create'));

    expect($crawler->filter('[data-testid="ingredient-row"]')->count())->toBe(1);
    expect($crawler->filter('select[name="materials[0][material_id]"]')->count())->toBe(1);
    expect($crawler->filter('input[name="materials[0][drops]"]')->count())->toBe(1);
    expect($crawler->filter('select[name="materials[0][dilution]"]')->count())->toBe(1);
});

test('Create form provides the button and template needed to add ingredients', function () {
    [, $crawler] = getPageCrawler($this->user, route('blends.create'));

    $addButton = $crawler->filter('[data-testid="add-ingredient"]');
    expect($addButton->count())->toBe(1);

    $template = $crawler->filter('template[data-testid="ingredient-template"]');
    expect($template->count())->toBe(1);
    expect($template->html())->toContain('__INDEX__');
});

it('rejects duplicate materials in the same blend submission', function () {
    $lavender = makeMaterial();
    $payload = blendPayload('Sunshine', [
        ingredient($lavender),
        ingredient($lavender),
    ]);

    $response = postAs($this->user, route('blends.store'), $payload)
        ->assertSessionHasErrors(['materials']);

    $this->assertDatabaseCount('blends', 0);
    $this->assertDatabaseCount('blend_version_ingredients', 0);
});

test('create blend shows inline validation error per field', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    $payload = blendPayload('', [
        ingredient($lavender),
        ingredient($neroli, ['drops' => '']),
    ]);

    $response = $this->from(route('blends.create'))
        ->followingRedirects()
        ->post(route('blends.store'), $payload);

    $crawler = crawl($response);

    // Missing blend name
    expect($crawler->filter('[data-testid="error-name"]')->count())->toBe(1);

    $neroliRow = $crawler->filter('[data-testid="ingredient-row"][data-index="1"]');
    expect($neroliRow->count())->toBe(1);
    // Missing neroli drops amount
    expect($neroliRow->filter('[data-testid="error-materials.1.drops"]')->count())->toBe(1);
});

test('Each blend shows a delete & edit button in the show page', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);

    [$blend, $version] = makeBlendWithVersion($this->user, 'Moonshine');
    addIngredient($version, $lavender);
    addIngredient($version, $neroli);

    [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));

    $blendHeader = $crawler->filter('header');

    expect($blendHeader->filter('h2')->text())->toBe('Moonshine');
    expect($blendHeader->selectLink('EDIT')->count())->toBe(1);
    expect($blendHeader->selectButton('DELETE')->count())->toBe(1);
});

test('user can delete a blend', function () {
    [$blend] = makeBlendWithVersion($this->user, 'Moonshine');

    $response = $this->delete(route('blends.destroy', $blend))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('blends', [
        'id' => $blend->id,
    ]);
});

test('user can view the edit form for a blend and it is prefilled', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    [$blend, $version] = makeBlendWithVersion($this->user, 'Moonshine');

    addIngredient($version, $lavender, 2);
    addIngredient($version, $neroli, 5);

    [, $crawler] = getPageCrawler($this->user, route('blends.edit', $blend));

    // Expect blend title
    expect($crawler->filter('input[name="name"]')->attr('value'))->toBe('Moonshine');

    $rows = $crawler->filter('[data-testid="ingredient-row"]');

    // Expect 2 ingredients
    expect($rows)->count()->toBe(2);

    // LAVENDER
    $LavenderRow = $rows->reduce(function ($node) use ($lavender) {
        return $node->filter('option[selected][value="'.$lavender->id.'"]')->count() > 0;
    });
    // Assert lavender ingredient is 1 and has 2 drops at 25%
    expect($LavenderRow->count())->toBe(1);
    expect($LavenderRow->filter('input[name*="[drops]"][value="2"]')->count())->toBe(1);
    expect($LavenderRow->filter('select[name*="[dilution]"] option[selected][value="25"]')->count())->toBe(1);

    // NEROLI
    $neroliRow = $rows->reduce(function ($node) use ($neroli) {
        return $node->filter('option[selected][value="'.$neroli->id.'"]')->count() > 0;
    });
    // Assert neroli ingredient is 1 and has 2 drops at 25%
    expect($neroliRow->count())->toBe(1);
    expect($neroliRow->filter('input[name*="[drops]"][value="5"]')->count())->toBe(1);
    expect($neroliRow->filter('select[name*="[dilution]"] option[selected][value="25"]')->count())->toBe(1);
});

test('user can update a blend from the edit form', function () {
    // Create materials
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);

    // Create Blend
    [$blend, $version] = makeBlendWithVersion($this->user, 'Moonshine');

    addIngredient($version, $lavender, 2, 25);
    addIngredient($version, $neroli, 5, 25);

    $editUrl = route('blends.edit', $blend);
    $updateUrl = route('blends.update', $blend);

    $payload = blendPayload('Moonshine-2', [
        ingredient($lavender, [
            'drops' => 10,
        ]),
        ingredient($neroli, [
            'drops' => 1,
            'dilution' => 10,
        ]),
    ]);

    // Update blend. Simulates user submiting request FROM the edit page
    $this->from($editUrl)
        ->put($updateUrl, $payload)
        ->assertRedirect(route('blends.show', $blend));

    // Assert changes
    $this->assertDatabaseHas('blends', [
        'id' => $blend->id,
        'name' => 'Moonshine-2',
    ]);

    $this->assertDatabaseHas('blend_version_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'drops' => 10,
        'dilution' => 25,
    ]);

    $this->assertDatabaseHas('blend_version_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $neroli->id,
        'drops' => 1,
        'dilution' => 10,
    ]);

    $this->assertDatabaseMissing('blend_version_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'drops' => 2,
        'dilution' => 25,
    ]);
});

test('blend version ingredients table has bottle_id column', function () {
    expect(Schema::hasColumn('blend_version_ingredients', 'bottle_id'))->toBeTrue();
});

test('when creating a blend it auto-assigns the single active bottle', function () {
    $postUrl = route('blends.store');
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    $lavenderBottle = makeBottle($lavender);
    $payload = blendPayload('blendWithBottleId', [
        ingredient($lavender),
        ingredient($neroli),
    ]);

    $response = postAs($this->user, $postUrl, $payload)
        ->assertRedirect();

    $blendId = basename($response->headers->get('Location'));

    $blend = Blend::findOrFail($blendId);
    $version = $blend->versions()->first();

    $this->assertDatabaseHas('blend_version_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'bottle_id' => $lavenderBottle->id,
    ]);
});

test('blend show page has a link for a bottle used for each ingredient that has a bottle assigned to it', function () {
    // create materials
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);

    // create bottle
    $lavenderBottle = makeBottle($lavender);

    // create blend + version + ingredients with bottle_id
    [$blend, $version] = makeBlendWithVersion($this->user, 'Forest');

    addIngredient($version, $lavender, 4, 25, $lavenderBottle->id);
    addIngredient($version, $neroli);

    $blendUrl = route('blends.show', $blend);
    $lavenderbottleUrl = route('materials.show', $lavender);
    $neroliBottleUrl = route('materials.show', $neroli);
    // visit show page
    [, $crawler] = getPageCrawler($this->user, $blendUrl);

    // assert bottle links
    expect($crawler->filter('a[href="'.$lavenderbottleUrl.'"]'))->toHaveCount(1);
    expect($crawler->filter('a[href="'.$neroliBottleUrl.'"]'))->toHaveCount(0);
});
