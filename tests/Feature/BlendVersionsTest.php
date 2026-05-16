<?php

use App\Models\BlendVersion;
use App\Models\User;

// -----------------------
// Create user for all tests
// -----------------------
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows a link to create a new version from an existing version', function () {
    // Create blend
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');
    // get HTML from blend show page
    [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));

    // Assert href to create new blend version is present
    $versionContainer = $crawler->filter('div[data-version="'.$version->version.'"]');
    $link = $versionContainer->selectLink('New Version');
    expect($link->link()->getUri())->toBe(route('blends.versions.create', [$blend, 'from' => $version->id]));
});

it('shows the blend version creation form for an existing blend', function () {
    // Create blend
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');
    // Visit create-version page
    [, $crawler] = getPageCrawler($this->user, route('blends.versions.create', $blend));
    // Assert form exists
    $form = $crawler->filter('form#create-blend-version-form');
    expect($form->count())->toBe(1);
});

it('creates a new blend version for an existing blend and redirects to the blend show page', function () {
    // Create blend
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

    // Create material & payload for new version
    $lavender = makeMaterial();
    $cumin = makeMaterial(['name' => 'Cumin']);

    $payload = versionPayload([
        ingredient($lavender),
        ingredient($cumin),
    ]);

    // Submit request to create new version
    $response = postAS($this->user, route('blends.versions.store', $blend), $payload);

    $blend->refresh();
    // Assert new version is created
    expect(BlendVersion::where('blend_id', $blend->id)->count())->toBe(2);

    // Assert redirect to blend show page
    $response->assertRedirect(route('blends.show', $blend));
});

it('shows every version belonging to a blend', function () {
    // Create blend + 2 versions
    [$blend, $version1] = makeBlendWithVersion($this->user, 'Test Blend');
    $version2 = $blend->versions()->create([
        'version' => $blend->nextVersionNumber(),
    ]);

    // Get HTML for blend show page
    [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
    // Assert both versions are shown
    expect($crawler->filter('div[data-testid="blend-version"]')->count())->toBe(2);
});

it('shows success message when a new version is created', function () {
    // Create blend
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

    // create materials
    $material1 = makeMaterial();
    $material2 = makeMaterial(['name' => 'petitgrain']);
    $ingredients = [ingredient($material1), ingredient($material2)];

    // Send request to create message and follow redirects
    $response = $this
        ->from(route('blends.versions.create', $blend))
        ->followingRedirects()
        ->post(route('blends.versions.store', $blend), ['materials' => $ingredients]);

    $newVersion = $blend->versions()->latest('id')->first();
    // Get HTML for vesion container
    $response = crawl($response);
    $versionContainer = $response->filter('div[data-version="'.$newVersion->version.'"]');

    // Assert success message
    expect($versionContainer->text())->toContain('Version 2 added');
});

test('user can update an existing version', function () {
    // Create blend + version
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

    // Add ingredients to version
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    addIngredient($version, $lavender);
    addIngredient($version, $neroli);

    // Prepare payload with updated ingredient data
    $payload = versionPayload([
        ingredient($lavender, [
            'drops' => 10,
            'dilution' => 1,
        ]),
        ingredient($neroli, [
            'drops' => 1,
            'dilution' => 1,
        ]),
    ]);

    // Send request to update version
    $response = patchAs($this->user, route('blends.versions.update', [$blend, $version]), $payload);

    $version->refresh();

    // Get HTML for blend show page
    [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
    $versionContainer = $crawler->filter('div[data-version="'.$version->version.'"]');

    // Assert redirect
    $response->assertRedirect(route('blends.show', $blend).'#version-'.$version->id);

    // Assert ingredients are updated
    $lavender = $versionContainer->filter('tr[data-material-id="'.$lavender->id.'"]');
    $neroli = $versionContainer->filter('tr[data-material-id="'.$neroli->id.'"]');

    expect($lavender->filter('td[data-col="drops"]')->text())->toBe('10');
    expect($lavender->filter('td[data-col="dilution"]')->text())->toBe('1%');

    expect($neroli->filter('td[data-col="drops"]')->text())->toBe('1');
    expect($neroli->filter('td[data-col="dilution"]')->text())->toBe('1%');
});

test('Editing a blend does not auto assign bottle ids when materials has more than one bottle available', function () {
    $lavender = makeMaterial();
    $galbanum = makeMaterial(['name' => 'Galbanum']);
    $neroli = makeMaterial(['name' => 'Neroli']);
    makeBottle($lavender);
    makeBottle($lavender);
    $response = postAs($this->user, route('blends.store'), blendPayload('Neroli Blend', [
        ingredient($neroli),
        ingredient($galbanum),
    ]));
    $blendId = basename($response->headers->get('Location'));
    $blend = Blend::findOrFail($blendId);
    $version = $blend->versions()->first();
    $this->from(route('blends.edit', $blend))
        ->put(route('blends.update', $blend), blendPayload($blend->name, [
            ingredient($neroli),
            ingredient($galbanum),
            ingredient($lavender),
        ]));
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'bottle_id' => null,
    ]);
})->skip();

test('Editing a blend auto-assings a bottle ID to newly added ingredients if only 1 available bottle exists', function () {
    $lavender = makeMaterial();
    $galbanum = makeMaterial(['name' => 'Galbanum']);
    $neroli = makeMaterial(['name' => 'Neroli']);
    $lavenderBottle = makeBottle($lavender);
    $response = postAs($this->user, route('blends.store'), blendPayload('Neroli Blend', [
        ingredient($neroli),
        ingredient($galbanum),
    ]));
    $blendId = basename($response->headers->get('Location'));
    $blend = Blend::findOrFail($blendId);
    $version = $blend->versions()->first();
    $this->from(route('blends.edit', $blend))
        ->put(route('blends.update', $blend), blendPayload($blend->name, [
            ingredient($neroli),
            ingredient($galbanum),
            ingredient($lavender),
        ]));
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'bottle_id' => $lavenderBottle->id,
    ]);
})->skip();

test('user can update a blend from the edit form', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    [$blend, $version] = makeBlendWithVersion($this->user, 'Moonshine');
    addIngredient($version, $lavender, null, 2, 25);
    addIngredient($version, $neroli, null, 5, 25);
    $payload = blendPayload('Moonshine-2', [
        ingredient($lavender, [
            'drops' => 10,
        ]),
        ingredient($neroli, [
            'drops' => 1,
            'dilution' => 10,
        ]),
    ]);
    $this->put(route('blends.update', $blend), $payload)
        ->assertRedirect(route('blends.show', $blend));
    $this->assertDatabaseHas('blends', [
        'id' => $blend->id,
        'name' => 'Moonshine-2',
    ]);
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'drops' => 10,
        'dilution' => 25,
    ]);
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $neroli->id,
        'drops' => 1,
        'dilution' => 10,
    ]);
    $this->assertDatabaseMissing('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'drops' => 2,
        'dilution' => 25,
    ]);
})->skip();

test('Editing a blend does not change bottle assignments of existing ingredients', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    $lavenderBottle = makeBottle($lavender);
    $payload = blendPayload('blendWithBottleId', [
        ingredient($lavender),
        ingredient($neroli),
    ]);
    $response = postAs($this->user, route('blends.store'), $payload);
    $blendId = basename($response->headers->get('Location'));
    $blend = Blend::findOrFail($blendId);
    $version = $blend->versions()->first();
    $this->from(route('blends.edit', $blend))
        ->put(route('blends.update', $blend), ['name' => 'Spice V2']);
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'bottle_id' => $lavenderBottle->id,
    ]);
})->skip();

test('user can view the edit form for a blend and it is prefilled', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    [$blend, $version] = makeBlendWithVersion($this->user, 'Moonshine');
    addIngredient($version, $lavender, null, 2);
    addIngredient($version, $neroli, null, 5);
    [, $crawler] = getPageCrawler($this->user, route('blends.edit', $blend));
    expect($crawler->filter('input[name="name"]')->attr('value'))->toBe('Moonshine');
    $rows = $crawler->filter('[data-testid="ingredient-row"]');
    expect($rows)->count()->toBe(2);
    $LavenderRow = $rows->reduce(function ($node) use ($lavender) {
        return $node->filter('option[selected][value="'.$lavender->id.'"]')->count() > 0;
    });
    expect($LavenderRow->count())->toBe(1);
    expect($LavenderRow->filter('input[name*="[drops]"][value="2"]')->count())->toBe(1);
    expect($LavenderRow->filter('select[name*="[dilution]"] option[selected][value="25"]')->count())->toBe(1);
    $neroliRow = $rows->reduce(function ($node) use ($neroli) {
        return $node->filter('option[selected][value="'.$neroli->id.'"]')->count() > 0;
    });
    expect($neroliRow->count())->toBe(1);
    expect($neroliRow->filter('input[name*="[drops]"][value="5"]')->count())->toBe(1);
    expect($neroliRow->filter('select[name*="[dilution]"] option[selected][value="25"]')->count())->toBe(1);
})->skip();
