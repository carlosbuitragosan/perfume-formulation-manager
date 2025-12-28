<?php

use App\Models\Blend;
use App\Models\User;

// Create user
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows a "create blend" button on the dashboard', function () {
    $createBlendsUrl = route('blends.create');
    [, $crawler] = getPageCrawler($this->user, route('dashboard'));

    $link = $crawler->filter("a[href=\"{$createBlendsUrl}\"]");

    expect($link->count())->toBe(1);
    expect($link->text())->toContain('Create Blend');

});

it('shows the create blend form', function () {
    $createBlendsUrl = route('blends.create');
    [, $crawler] = getPageCrawler($this->user, $createBlendsUrl);

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
    $postUrl = route('blends.store');

    $response = postAs($this->user, $postUrl, [
        'name' => 'Sunshine',
        'materials' => [
            [
                'material_id' => $lavender->id,
                'drops' => 1,
                'dilution' => 25,
            ],
            [
                'material_id' => $galbanum->id,
                'drops' => 1,
                'dilution' => 1,
            ],
        ],
    ]);

    $this->assertDatabaseHas('blends', [
        'user_id' => $this->user->id,
        'name' => 'Sunshine',
    ]);

    $blend = Blend::where('user_id', $this->user->id)
        ->where('name', 'Sunshine')
        ->firstOrFail();

    $redirectUrl = route('blends.show', ['blend' => $blend]);

    $response->assertRedirect($redirectUrl);
});

it('shows existing blend on the dashboard', function () {
    $blend = Blend::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Sunshine',
    ]);

    [, $crawler] = getPageCrawler($this->user, route('dashboard'));

    $link = $crawler->filter('a[href="'.route('blends.show', $blend).'"]');

    expect($link->count())->toBe(1);
    expect($link->text())->toContain('Sunshine');
});

it('shows blend version with ingredients and pure % breakdown', function () {
    $lavender = makeMaterial();
    $galbanum = makeMaterial(['name' => 'Galbanum']);
    $postUrl = route('blends.store');

    $response = postAs($this->user, $postUrl, [
        'name' => 'Sunshine',
        'materials' => [
            [
                'material_id' => $lavender->id,
                'drops' => 1,
                'dilution' => 25,
            ],
            [
                'material_id' => $galbanum->id,
                'drops' => 1,
                'dilution' => 1,
            ],
        ],
    ]);

    $blend = Blend::where('user_id', $this->user->id)
        ->where('name', 'Sunshine')
        ->firstOrFail();

    $redirectUrl = route('blends.show', $blend);

    $response->assertRedirect($redirectUrl);

    [, $crawler] = getPageCrawler($this->user, $redirectUrl);

    expect($crawler->filter('header')->text())->toContain($blend->name);

    $version = $crawler->filter('div[data-testid="blend-version"][data-version="1.0"]');
    expect($version->count())->toBe(1);

    $lavenderRow = $crawler->filter('tr[data-testid="blend-ingredient-row"][data-material-id="'.$lavender->id.'"]');
    expect($lavenderRow->count())->toBe(1);
    expect($lavenderRow->filter('[data-col="material"]')->text())->toBe($lavender->name);
    expect($lavenderRow->filter('[data-col="drops"]')->text())->toBe('1');
    expect($lavenderRow->filter('[data-col="dilution"]')->text())->toBe('25%');
    expect($lavenderRow->filter('[data-col="pure_pct"]')->text())->toBe('96.15%');

    $galbanumRow = $crawler->filter('tr[data-testid="blend-ingredient-row"][data-material-id="'.$galbanum->id.'"]');
    expect($galbanumRow->count())->toBe(1);
    expect($galbanumRow->filter('[data-col="material"]')->text())->toBe($galbanum->name);
    expect($galbanumRow->filter('[data-col="drops"]')->text())->toBe('1');
    expect($galbanumRow->filter('[data-col="dilution"]')->text())->toBe('1%');
    expect($galbanumRow->filter('[data-col="pure_pct"]')->text())->toBe('3.85%');
});

test('create form shows only one ingredient row initially and provides hooks to add more', function () {
    [, $crawler] = getPageCrawler($this->user, route('blends.create'));

    expect($crawler->filter('[data-testid="ingredient-row"]')->count())->toBe(1);
    expect($crawler->filter('select[name="materials[0][material_id]"]')->count())->toBe(1);
    expect($crawler->filter('input[name="materials[0][drops]"]')->count())->toBe(1);
    expect($crawler->filter('select[name="materials[0][dilution]"]')->count())->toBe(1);
    expect($crawler->filter('[data-testid="add-ingredient"]')->count())->toBe(1);
    expect($crawler->filter('template[data-testid="ingredient-template"]')->count())->toBe(1);
});

test('user can add a second ingredient row', function () {
    [, $crawler] = getPageCrawler($this->user, route('blends.create'));

    $addButton = $crawler->filter('[data-testid="add-ingredient"]');
    expect($addButton->count())->toBe(1);

    $template = $crawler->filter('template[data-testid="ingredient-template"]');
    expect($template->count())->toBe(1);
    expect($template->html())->toContain('__INDEX__');
});

it('rejects duplicate materials in the same blend submission', function () {
    $material = makeMaterial();
    $postUrl = route('blends.store');
    $blend = [
        'name' => 'Sunshine',
        'materials' => [
            ['material_id' => $material->id, 'drops', 1, 'dilution' => 25],
            ['material_id' => $material->id, 'drops', 2, 'dilution' => 25],
        ],
    ];

    $response = postAs($this->user, $postUrl, $blend);

    $response->assertSessionHasErrors(['materials']);
    $this->assertDatabaseCount('blend_version_ingredients', 0);

});

test('create blend shows inline validation error per field', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    $createUrl = route('blends.create');
    $postUrl = route('blends.store');
    $payload = [
        'name' => '',
        'materials' => [
            [
                'material_id' => $lavender->id,
                'drops' => 1,
                'dilution' => 25,
            ],
            [
                'material_id' => $neroli->id,
                'drops' => '',
                'dilution' => 25,
            ],

        ],
    ];
    $response = $this->from($createUrl)->post($postUrl, $payload);

    $response->assertRedirect($createUrl);
    $response->assertSessionHasErrors(['name', 'materials.1.drops']);
    $page = $this->get($createUrl)->assertOk();

    $crawler = crawl($page);

    expect($crawler->filter('[data-testid="error-name"]')->count())->toBe(1);

    $row1 = $crawler->filter('[data-testid="ingredient-row"][data-index="1"]');
    expect($row1->count())->toBe(1);
    expect($row1->filter('[data-testid="error-materials.1.drops"]')->count())->toBe(1);
});

test('Each blend shows a delete & edit button in the show page', function () {
    $lavender = makeMaterial();
    $neroli = makeMaterial(['name' => 'Neroli']);
    $createUrl = route('blends.create');
    $postUrl = route('blends.store');

    $payload = [
        'name' => 'Moonshine',
        'materials' => [
            [
                'material_id' => $lavender->id,
                'drops' => 1,
                'dilution' => 25,
            ],
            [
                'material_id' => $neroli->id,
                'drops' => '5',
                'dilution' => 25,
            ],
        ],
    ];

    $response = postAs($this->user, $postUrl, $payload);

    $blend = Blend::where('user_id', $this->user->id)
        ->where('name', 'Moonshine')
        ->firstOrFail();
    $redirectUrl = route('blends.show', $blend);

    $response->assertRedirect($redirectUrl);

    [, $crawler] = getPageCrawler($this->user, $redirectUrl);

    $blendHeader = $crawler->filter('header');

    expect($blendHeader->text())->toContain('Moonshine');
    expect($blendHeader->text())->toContain('EDIT');
    expect($blendHeader->text())->toContain('DELETE');
});
