<?php

use App\Models\Blend;
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

test('when editing an existing version, the edit form is prefilled', function () {
    // Create materials, blend + version with ingredients
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    [$blend, $version] = makeBlendWithVersion($this->user, 'Moonshine');
    addIngredient($version, $lavender, null, 2);
    addIngredient($version, $neroli, null, 5);

    // Get HTML for blend edit page
    [, $crawler] = getPageCrawler($this->user, route('blends.versions.edit', [$blend, $version]));

    // Assert 2 rows present
    $rows = $crawler->filter('[data-testid="ingredient-row"]');
    expect($rows)->count()->toBe(2);

    // Assert lavender values are prefilled
    $LavenderRow = $rows->reduce(function ($node) use ($lavender) {
        return $node->filter('option[selected][value="'.$lavender->id.'"]')->count() > 0;
    });
    expect($LavenderRow->count())->toBe(1);
    expect($LavenderRow->filter('input[name*="[drops]"][value="2"]')->count())->toBe(1);
    expect($LavenderRow->filter('select[name*="[dilution]"] option[selected][value="25"]')->count())->toBe(1);

    // Assert neroli values are prefilled
    $neroliRow = $rows->reduce(function ($node) use ($neroli) {
        return $node->filter('option[selected][value="'.$neroli->id.'"]')->count() > 0;
    });
    expect($neroliRow->count())->toBe(1);
    expect($neroliRow->filter('input[name*="[drops]"][value="5"]')->count())->toBe(1);
    expect($neroliRow->filter('select[name*="[dilution]"] option[selected][value="25"]')->count())->toBe(1);
});

it('when creating a new version, the create form is prefilled from an existing version', function () {
    // Create blend
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');
    // Add ingredients to version
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    addIngredient($version, $lavender);
    addIngredient($version, $neroli);

    // Visit create-version page with from query param
    [, $crawler] = getPageCrawler($this->user, route('blends.versions.create', [$blend, 'from' => $version->id]));

    // Assert form is prefilled with ingredient data
    $rows = $crawler->filter('[data-testid="ingredient-row"]');
    expect($rows->count())->toBe(2);
    expect($rows->filter('option[selected][value="'.$lavender->id.'"]')->count())->toBe(1);
    expect($rows->filter('option[selected][value="'.$neroli->id.'"]')->count())->toBe(1);
});

it('creates a new blend version for an existing one and redirects to the blend show page', function () {
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
    $response
        ->assertRedirect(route('blends.show', $blend).'#version-'.$blend->versions()->latest('id')->first()->id);
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

test('updates version ingredients in storage', function () {
    // Create materials
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);

    // Create blend + version with ingredients
    [$blend, $version] = makeBlendWithVersion($this->user, 'Moonshine');
    addIngredient($version, $lavender, null, 5, 25);
    addIngredient($version, $neroli, null, 5, 25);

    // Update version
    $this->put(
        route('blends.versions.update', [$blend, $version]),
        versionPayload([
            ingredient($lavender, [
                'drops' => 10,
            ]),
            ingredient($neroli, [
                'drops' => 1,
            ]),
        ]))
        ->assertRedirect(route('blends.show', $blend).'#version-'.$version->id);

    // Assert database has updated ingredient data
    $this->assertDatabaseHas('blends', [
        'id' => $blend->id,
        'name' => 'Moonshine',
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
        'dilution' => 25,
    ]);
    $this->assertDatabaseMissing('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'drops' => 5,
        'dilution' => 25,
    ]);
});

test('shows updated version ingredients on the blend show page', function () {
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

test('Editing a blend version does not auto assign bottle ids when materials has more than one bottle available', function () {
    // Create materials and 2 bottles for lavender
    $lavender = makeMaterial();
    $galbanum = makeMaterial(['name' => 'Galbanum']);
    $neroli = makeMaterial(['name' => 'Neroli']);
    makeBottle($lavender);
    makeBottle($lavender);

    // Post request to new blend
    $response = postAs($this->user, route('blends.store'), blendPayload('Neroli Blend', [
        ingredient($neroli),
        ingredient($galbanum),
    ]));

    // get new blend and version from DB
    $blendId = basename($response->headers->get('Location'));
    $blend = Blend::findOrFail($blendId);
    $version = $blend->versions()->first();

    // Update blend. Add lavender which has 2 available bottles
    $this->from(route('blends.versions.edit', [$blend, $version]))
        ->put(route('blends.versions.update', [$blend, $version]), versionPayload([
            ingredient($neroli),
            ingredient($galbanum),
            ingredient($lavender),
        ]));

    // Assert no bottle_id was assigned to lavender
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'bottle_id' => null,
    ]);
});

test('Editing a blend version auto-assings a bottle ID to newly added ingredients if only 1 available bottle exists', function () {
    // Create material and 1 bottle for auto-assignment
    $lavender = makeMaterial();
    $galbanum = makeMaterial(['name' => 'Galbanum']);
    $neroli = makeMaterial(['name' => 'Neroli']);
    $lavenderBottle = makeBottle($lavender);

    // Post  request to new blend
    $response = postAs(
        $this->user,
        route('blends.store'),
        blendPayload('Neroli Blend', [
            ingredient($neroli),
            ingredient($galbanum),
        ])
    );

    // Get the newly created blend and version from DB
    $blendId = basename($response->headers->get('Location'));
    $blend = Blend::findOrFail($blendId);
    $version = $blend->versions()->first();

    // Update blend. Add lavender which has 1 available bottle and should be auto-assigned
    $this->from(route('blends.versions.edit', [$blend, $version]))
        ->put(route('blends.versions.update', [$blend, $version]), versionPayload([
            ingredient($neroli),
            ingredient($galbanum),
            ingredient($lavender),
        ]));

    // Assert lavender ingredient was assigned the bottle ID
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'bottle_id' => $lavenderBottle->id,
    ]);
});

test('Editing a blend version does not change bottle assignments of existing ingredients', function () {
    // Create materials + 1 bottle for assignment
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    $lavenderBottle = makeBottle($lavender);

    // Post request to new blend which assigns bottle to lavender ingredient
    $response = postAs($this->user, route('blends.store'), blendPayload('New Blend', [
        ingredient($lavender),
        ingredient($neroli),
    ]));

    // Get the newly created blend and version from DB
    $blendId = basename($response->headers->get('Location'));
    $blend = Blend::findOrFail($blendId);
    $version = $blend->versions()->first();

    // Update blend. Update ingredient data without changing materials. Existing bottle assignment should be preserved even though ingredients are deleted and recreated in DB
    $this->from(route('blends.versions.edit', [$blend, $version]))
        ->put(route('blends.versions.update', [$blend, $version]), ['name' => 'Spice V2']);

    // Assert lavender ingredient still has the same bottle ID
    $this->assertDatabaseHas('blend_ingredients', [
        'blend_version_id' => $version->id,
        'material_id' => $lavender->id,
        'bottle_id' => $lavenderBottle->id,
    ]);
});

it('it displays version actions within the blend version card', function () {
    // Create blend + 2 versions
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');
    $version2 = $blend->versions()->create([
        'version' => $blend->nextVersionNumber(),
    ]);

    // Get HTML from blend show page and version container
    [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
    $versionCard = $crawler->filter('div[data-testid="blend-version"][data-version="'.$version->version.'"]');

    // Assert version actions are within the blend version card
    expect($versionCard->selectLink('EDIT')->count())->toBe(1);
    expect($versionCard->selectLink('New Version')->count())->toBe(1);
    expect($versionCard->selectButton('DELETE')->count())->toBe(1);
});

test('user can delete a blend version', function () {
    // Create materials,  blend + 2 versions
    [$blend, $version1] = makeBlendWithVersion($this->user, 'Test Blend');

    $version2 = $blend->versions()->create([
        'version' => $blend->nextVersionNumber(),
    ]);

    // Send request to delete version
    $response = $this->from(route('blends.show', $blend))
        ->delete(route('blends.versions.destroy', [$blend, $version2]));

    // Assert version is deleted and version
    expect(BlendVersion::find($version2->id))->toBeNull();

    // Assert redirect to blend show page
    $response->assertRedirect(route('blends.show', $blend));
});

test('deleting a blend version shows a success message', function () {
    // Create blend + 2 versions
    [$blend, $version1] = makeBlendWithVersion($this->user, 'Test Blend');
    $version2 = $blend->versions()->create([
        'version' => $blend->nextVersionNumber(),
    ]);

    // Send request to delete version and follow redirects
    $response = $this->from(route('blends.show', $blend))
        ->followingRedirects()
        ->delete(route('blends.versions.destroy', [$blend, $version1]));

    // Assert success message is shown
    $response->assertSee("Version {$version1->version} deleted");
});

it('hides the delete button when a blend only has one version', function () {
    // Create blend + version
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

    // Get HTML from blend show page and version container
    [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
    $versionCard = $crawler->filter('div[data-version="'.$version->version.'"]');

    // Assert delete button is not present
    expect($versionCard->selectButton('DELETE')->count())->toBe(0);
});

test('prevent deleting the only version of a blend', function () {
    // Create blend + version
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

    // Send request to delete the only version of the blend
    $response = $this->from(route('blends.show', $blend))
        ->followingRedirects()
        ->delete(route('blends.versions.destroy', [$blend, $version]));

    // Assert version is not deleted
    expect(BlendVersion::find($version->id))->not()->toBeNull();
});
