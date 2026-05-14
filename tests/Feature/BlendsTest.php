<?php

use App\Models\Blend;
use App\Models\Bottle;
use App\Models\User;

// -----------------------
// Create user for all tests
// -----------------------
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ==========================================================
// Dashboard & Navigation
// ==========================================================
describe('Dashboard & Navigation', function () {
    it('Dashboard shows a "create blend" link that leads to the blend creation page', function () {
        [, $crawler] = getPageCrawler($this->user, route('dashboard'));
        $link = $crawler->selectLink('Create Blend');
        expect($link->count())->toBe(1);
        expect($link->link()->getUri())->toContain('/blends/create');
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
});

// ==========================================================
// Blend Creation (Form & Submission)
// ==========================================================
describe('Blend Creation (Form & Submission)', function () {
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
        $response->assertRedirect(route('blends.show', $blend));
        $this->assertDatabaseHas('blends', [
            'user_id' => $this->user->id,
            'name' => 'Sunshine',
        ]);
    });

    test('create form shows two ingredient rows with the expected inputs', function () {
        [, $crawler] = getPageCrawler($this->user, route('blends.create'));
        expect($crawler->filter('[data-testid="ingredient-row"]')->count())->toBe(2);

        expect($crawler->filter('select[name="materials[0][material_id]"]')->count())->toBe(1);
        expect($crawler->filter('select[name="materials[1][material_id]"]')->count())->toBe(1);

        expect($crawler->filter('input[name="materials[0][drops]"]')->count())->toBe(1);
        expect($crawler->filter('input[name="materials[1][drops]"]')->count())->toBe(1);

        expect($crawler->filter('select[name="materials[0][dilution]"]')->count())->toBe(1);
        expect($crawler->filter('select[name="materials[1][dilution]"]')->count())->toBe(1);
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
        $this->assertDatabaseCount('blend_ingredients', 0);
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
        expect($crawler->filter('[data-testid="error-name"]')->count())->toBe(1);
        $neroliRow = $crawler->filter('[data-testid="ingredient-row"][data-index="1"]');
        expect($neroliRow->count())->toBe(1);
        expect($neroliRow->filter('[data-testid="error-materials.1.drops"]')->count())->toBe(1);
    });
});

// ==========================================================
// Blend Display & Breakdown
// ==========================================================
describe('Blend Display & Breakdown', function () {
    it('shows blend version with ingredients and pure % breakdown', function () {
        // Create materials
        $lavender = makeMaterial();
        $galbanum = makeMaterial(['name' => 'Galbanum']);

        // Create blend
        [$blend, $version] = makeBlendWithVersion($this->user, 'Sunshine');
        $lavenderIngredient = addIngredient($version, $lavender);
        $galbanumIngredient = addIngredient($version, $galbanum);

        // Get show page HTML
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));

        // expect name & version
        expect($crawler->filter('header')->text())->toContain($blend->name);
        expect($crawler->filter('div[data-testid="blend-version"][data-version="'.$version->version.'"]')->count())->toBe(1);

        // Expect lavender
        $lavenderRow = $crawler->filter('tr[data-material-id="'.$lavenderIngredient->material_id.'"]');
        expect($lavenderRow->count())->toBe(1);
        expect($lavenderRow->filter('[data-col="material"]')->text())->toBe($lavender->name);
        expect($lavenderRow->filter('[data-col="drops"]')->text())->toBe('4');
        expect($lavenderRow->filter('[data-col="dilution"]')->text())->toBe('25%');
        expect($lavenderRow->filter('[data-col="pure_pct"]')->text())->toBe('50%');

        // Expect galbanum
        $galbanumRow = $crawler->filter('tr[data-material-id="'.$galbanumIngredient->material_id.'"]');
        expect($galbanumRow->count())->toBe(1);
        expect($galbanumRow->filter('[data-col="material"]')->text())->toBe($galbanum->name);
        expect($galbanumRow->filter('[data-col="drops"]')->text())->toBe('4');
        expect($galbanumRow->filter('[data-col="dilution"]')->text())->toBe('25%');
        expect($galbanumRow->filter('[data-col="pure_pct"]')->text())->toBe('50%');
    });

    test('blend show indicates which ingredients have a bottle assigned', function () {
        // Create ingredients, bottle & blend
        $lavender = makeMaterial();
        $neroli = makeMaterial(['name' => 'Neroli']);
        $lavenderBottle = makeBottle($lavender);
        [$blend, $version] = makeBlendWithVersion($this->user, 'Forest');
        $lavenderIngredient = addIngredient($version, $lavender, $lavenderBottle->id, 4, 25);
        $neroliIngredient = addIngredient($version, $neroli);

        // get HTML
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));

        // Expect
        expect($crawler
            ->filter('a[data-ingredient-id="'.$lavenderIngredient->id.'"]')
            ->attr('class'))
            ->not->toContain('opacity-60');

        expect($crawler
            ->filter('a[data-ingredient-id="'.$neroliIngredient->id.'"]')
            ->attr('class'))
            ->toContain('opacity-60');
    });

    test('Each ingredient in the blend show page links to its material page with ingredient context', function () {
        // make material & blend
        $lavender = makeMaterial();
        $neroli = makeMaterial(['name' => 'neroli']);
        [$blend, $version] = makeBlendWithVersion($this->user, 'new perfume');
        $lavenderIngredient = addIngredient($version, $lavender);

        // Get blend show page html
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
        $lavenderUri = $crawler->filter('a[data-ingredient-id="'.$lavenderIngredient->id.'"]')->link()->getUri();

        // Assert ingredients have a link to related material
        expect($lavenderUri)->toContain(route('materials.show', $lavender));
        expect($lavenderUri)->toContain('ingredient='.$lavenderIngredient->id);
    });

    test('shows blend ingredients listed by pyramid values', function () {
        // Create materials
        $topHeartMaterial = makeMaterial(['name' => 'top heart', 'pyramid' => ['top', 'heart']]);
        $topMaterial = makeMaterial(['name' => 'top', 'pyramid' => ['top']]);
        $heartBaseMaterial = makeMaterial(['name' => 'heart base', 'pyramid' => ['heart', 'base']]);
        $heartMaterial = makeMaterial(['name' => 'heart', 'pyramid' => ['heart']]);
        $baseMaterial = makeMaterial(['name' => 'base', 'pyramid' => ['base']]);
        $topHeartBaseMaterial = makeMaterial(['name' => 'top heart base', 'pyramid' => ['top', 'heart', 'base']]);

        // Create blend with ingredients
        [$blend, $version] = makeBlendWithVersion($this->user, 'Pyramid Test');
        $materials = [
            $topMaterial,
            $topHeartMaterial,
            $heartMaterial,
            $heartBaseMaterial,
            $topHeartBaseMaterial,
            $baseMaterial,
        ];
        foreach ($materials as $material) {
            addIngredient($version, $material);
        }

        // Get HTML from blend show page and version container
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
        $ingredientsTable = $crawler->filter('div[data-testid="blend-version"][data-version="'.$version->version.'"] tbody');
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

    test('maps pyramid values to correct ingredient variant', function () {
        // Create materials
        $topHeartMaterial = makeMaterial(['name' => 'top heart', 'pyramid' => ['top', 'heart']]);
        $topMaterial = makeMaterial(['name' => 'top', 'pyramid' => ['top']]);
        $heartBaseMaterial = makeMaterial(['name' => 'heart base', 'pyramid' => ['heart', 'base']]);
        $heartMaterial = makeMaterial(['name' => 'heart', 'pyramid' => ['heart']]);
        $baseMaterial = makeMaterial(['name' => 'base', 'pyramid' => ['base']]);
        $topHeartBaseMaterial = makeMaterial(['name' => 'top heart base', 'pyramid' => ['top', 'heart', 'base']]);

        // Create blend with ingredients
        [$blend, $version] = makeBlendWithVersion($this->user, 'Pyramid Test');
        $topIngredient = addIngredient($version, $topMaterial);
        $topHeartIngredient = addIngredient($version, $topHeartMaterial);
        $heartIngredient = addIngredient($version, $heartMaterial);
        $heartBaseIngredient = addIngredient($version, $heartBaseMaterial);
        $topHeartBaseIngredient = addIngredient($version, $topHeartBaseMaterial);
        $baseIngredient = addIngredient($version, $baseMaterial);

        // Assert ingredients' variants
        expect($topIngredient->variant())->toBe('top');
        expect($heartIngredient->variant())->toBe('heart');
        expect($baseIngredient->variant())->toBe('base');
        expect($topHeartIngredient->variant())->toBe('top-heart');
        expect($heartBaseIngredient->variant())->toBe('heart-base');
        expect($topHeartBaseIngredient->variant())->toBe('all');

    });

    it('it displays version actions within the blend version card', function () {
        // Create blend
        [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

        // Get HTML from blend show page and version container
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
        $versionCard = $crawler->filter('div[data-testid="blend-version"][data-version="'.$version->version.'"]');

        // Assert version actions are within the blend version card
        expect($versionCard->filter('a')->text())->toContain('EDIT');
        expect($versionCard->filter('form')->text())->toContain('DELETE');
    });

    it('it displays a link to create a new blend version', function () {
        // Create blend
        [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');
        // get HTML from blend show page
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
        // Assert button to create new blend is present
        expect($crawler->selectLink('New Version')->count())->toBe(1);
    });
});

// ==========================================================
// Blend Deletion
// ==========================================================
describe('Blend Deletion', function () {
    test('user can delete a blend', function () {
        [$blend] = makeBlendWithVersion($this->user, 'Moonshine');
        $response = $this->delete(route('blends.destroy', $blend))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('blends', [
            'id' => $blend->id,
        ]);
    });

    test('show confirmation message when attempting to delete a blend', function () {
        // Create a blend
        [$blend] = makeBlendWithVersion($this->user, 'Blend');

        // Get HTML from blends show page for the delete form
        [, $crawler] = getPagecrawler($this->user, route('blends.show', $blend));
        $form = $crawler
            ->selectButton('DELETE')
            ->ancestors()
            ->filter('form')
            ->first();
        $onsubmit = $form->attr('onsubmit');

        // Assert confirmation
        expect($onsubmit)->toContain("Delete {$blend->name}?");

    });

    test('shows confirmation when a blend has been deleted', function () {
        // Create blend
        [$blend] = makeBlendWithVersion($this->user, 'Test Blend');

        // Send request to delete blend following redirects
        $response = $this
            ->from(route('blends.show', $blend))
            ->followingRedirects()
            ->delete(route('blends.show', $blend));

        // Get HTML from the blends dashboard
        $crawler = crawl($response);

        // Assert message
        expect($crawler->text())->toContain("{$blend->name} deleted");
    });
});

// ==========================================================
// Blend Editing
// ==========================================================
describe('Blend Editing', function () {
    test("user can update a blend's name", function () {
        // Create blend + payload
        [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

        // Send request to update blend name
        $response = patchAs($this->user, route('blends.update', $blend), [
            'name' => 'New Blend Name',
        ]);

        // Assert Redirected to blend show page
        $response->assertRedirect(route('blends.show', $blend));

        // Assert new name is shown
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
        expect($crawler->filter('header')->text())->toContain('New Blend Name');
    });

    test('editing a blend does not assign a bottle_id when a new ingredient has multiple available bottles', function () {
        $lavender = makeMaterial();
        $neroli = makeMaterial(['name' => 'Neroli']);
        makeBottle($lavender);
        makeBottle($lavender);
        $response = postAs($this->user, route('blends.store'), blendPayload('Neroli Blend', [
            ingredient($neroli),
            ingredient($lavender),
        ]));
        $blendId = basename($response->headers->get('Location'));
        $blend = Blend::findOrFail($blendId);
        $version = $blend->versions()->first();
        $this->assertDatabaseHas('blend_ingredients', [
            'blend_version_id' => $version->id,
            'material_id' => $lavender->id,
            'bottle_id' => null,
        ]);
    });
});

// ==========================================================
// Database Schema
// ==========================================================
describe('Database Schema', function () {
    test('blend version ingredients table has bottle_id column', function () {
        expect(Schema::hasColumn('blend_ingredients', 'bottle_id'))->toBeTrue();
    });

    test('when creating a blend it auto-assigns the single available bottle', function () {
        $lavender = makeMaterial();
        $neroli = makeMaterial(['name' => 'Neroli']);
        $lavenderBottle = makeBottle($lavender);
        $payload = blendPayload('blendWithBottleId', [
            ingredient($lavender),
            ingredient($neroli),
        ]);
        $response = postAs($this->user, route('blends.store'), $payload)
            ->assertRedirect();
        $blendId = basename($response->headers->get('Location'));
        $blend = Blend::findOrFail($blendId);
        $version = $blend->versions()->first();
        $this->assertDatabaseHas('blend_ingredients', [
            'blend_version_id' => $version->id,
            'material_id' => $lavender->id,
            'bottle_id' => $lavenderBottle->id,
        ]);
    });
});

// ==========================================================
// Bottle & Material Constraints
// ==========================================================
describe('blend ingredient bottle assignment', function () {

    test('creating a bottle with ingredient context assigns it to that blend ingredient', function () {
        // Create a blend without any bottles
        $lavender = makeMaterial();
        [$blend, $version] = makeBlendWithVersion($this->user, 'Blend');
        $lavenderIngredient = addIngredient($version, $lavender);

        // Post a bottle for an ingredient in the blend
        postAs($this->user,
            route('materials.bottles.store', $lavender).'?ingredient='.$lavenderIngredient->id, bottlePayload());

        // Refresh DB
        $lavenderIngredient->refresh();

        // Assert bottle has been assigned to the blend ingredient
        expect($lavenderIngredient->bottle_id)->not->toBe(null);
    });
});
