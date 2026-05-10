<?php

use App\Models\Blend;
use App\Models\BlendVersion;
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

    test('Creating new blend shows success message', function () {
        // create materials
        $material1 = makeMaterial();
        $material2 = makeMaterial(['name' => 'petitgrain']);
        $ingredients = [ingredient($material1), ingredient($material2)];

        // Send request to create message and follow redirects
        $response = $this
            ->from(route('blends.create'))
            ->followingRedirects()
            ->post(route('blends.store'),
                blendPayload('test blend', $ingredients));

        // Get HTML for vesion container
        $response = crawl($response);
        $versionContainer = $response->filter('div[data-testId="blend-version"]')->first();

        // Assert success message
        expect($versionContainer->text())->toContain('Test Blend added');
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
        expect($crawler->filter('div[data-testid="blend-version"][data-version="1.0"]')->count())->toBe(1);

        // Expect lavender
        $lavenderRow = $crawler->filter('tr[data-ingredient-id="'.$lavenderIngredient->id.'"]');
        expect($lavenderRow->count())->toBe(1);
        expect($lavenderRow->filter('[data-col="material"]')->text())->toBe($lavender->name);
        expect($lavenderRow->filter('[data-col="drops"]')->text())->toBe('4');
        expect($lavenderRow->filter('[data-col="dilution"]')->text())->toBe('25%');
        expect($lavenderRow->filter('[data-col="pure_pct"]')->text())->toBe('50%');

        // Expect galbanum
        $galbanumRow = $crawler->filter('tr[data-ingredient-id="'.$galbanumIngredient->id.'"]');
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
        $ingredientsTable = $crawler->filter('div[data-testid="blend-version"][data-version="1.0"] tbody');
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
    });

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
    });

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
    });

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
    });

    test('Updating a blend shows success message', function () {
        // Create materials & blend
        $material1 = makeMaterial();
        $material2 = makeMaterial(['name' => 'neroli']);
        [$blend, $version] = makeBlendWithVersion($this->user, 'Blend');

        // Send request to update blend
        $response = $this
            ->from(route('blends.show', $blend))
            ->followingRedirects()
            ->patch(route('blends.update', $blend),
                blendPayload('Blend 2', [
                    ingredient($material1),
                    ingredient($material2),
                ])
            );

        // Get HTML from version container
        $crawler = crawl($response);
        $versionContainer = $crawler->filter('div[data-testId="blend-version"]');
        $blend->refresh();

        // Assert message
        expect($crawler->text())->toContain("{$blend->name} updated");
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

describe('blend versioning', function () {
    it('shows a link to create a new version for the blend', function () {
        // Create blend
        [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');
        // get HTML from blend show page
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
        // Assert href to create new blend version is present
        $link = $crawler->selectLink('New Version');
        expect($link->link()->getUri())->toBe(route('blends.versions.create', $blend));
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

        $payload = [
            'materials' => [
                [
                    'material_id' => $lavender->id,
                    'drops' => 10,
                    'dilution' => 25,
                ],
                [
                    'material_id' => $cumin->id,
                    'drops' => 10,
                    'dilution' => 25,
                ],
            ],
        ];

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
            'version' => '2.0',
        ]);

        // Get HTML for blend show page
        [, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
        // Assert both versions are shown
        expect($crawler->filter('div[data-testid="blend-version"]')->count())->toBe(2);
    });

});
