<?php

use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

// Create user
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ===================
// Database Schema
// ===================
describe('Database Schema', function () {
    it('has the materials table with the expected columns', function () {
        expect(Schema::hasTable('materials'))->toBeTrue();
        expect(Schema::hasColumns('materials', [
            'id', 'name', 'botanical', 'notes', 'pyramid', 'families', 'functions', 'safety', 'effects', 'ifra_max_pct', 'created_at', 'updated_at', 'user_id',
        ]))->toBeTrue();
        expect(Schema::hasColumn('materials', 'category'))->toBeFalse();
    });

    it('can create a material with the minimal fields', function () {
        $material = makeMaterial();
        expect($material->id)->not->toBeNull();
        expect($material->pyramid)->not->toBeNull();
        $this->assertDatabaseHas('materials', [
            'name' => 'Lavender',
            'user_id' => $this->user->id,
        ]);
    });

    it('casts pyramid to array when set', function () {
        $material = makeMaterial();
        $material->refresh();
        expect($material->pyramid)->toEqual(['top', 'heart']);
    });
});

describe('authentication', function () {
    it('redirects guests to login on /materials', function () {
        auth()->logout();
        $this->get('/materials')->assertRedirect('/login');
    });
});

describe('creating materials', function () {
    it('validates name when creating a material', function () {
        $postUrl = route('materials.store');
        postAs($this->user, $postUrl, materialPayload(['name' => '']))
            ->assertSessionHasErrors(['name']);
    });

    it('createa a material and redirects to index', function () {
        $postUrl = route('materials.store');
        $response = postAs($this->user, $postUrl, materialPayload());
        $material = Material::latest('id')->first();
        $redirectUrl = route('materials.index').'#material-'.$material->id;
        $response->assertRedirect($redirectUrl);
        $this->assertDatabaseHas('materials', [
            'name' => 'Lavender',
            'id' => $material->id,
        ]);
    });

    it('persists botanical when provided', function () {
        $postUrl = route('materials.store');
        $response = postAs($this->user, $postUrl, materialPayload());
        $material = Material::latest('id')->first();
        $redirectUrl = route('materials.index').'#material-'.$material->id;
        $this->assertDatabaseHas('materials', [
            'user_id' => $this->user->id,
            'id' => $material->id,
            'name' => 'Lavender',
            'botanical' => 'Lavandula Angustifolia',
        ]);
    });

    it('rejects duplicate material names (case-insensitive)', function () {
        $postUrl = route('materials.store');
        makeMaterial(); // seeds Lavender
        expect(Material::count())->toBe(1);
        // tries to post same material (Lavender)
        postAs($this->user, $postUrl, materialPayload(['name' => 'lavender']))
            ->assertSessionHasErrors(['name']);
        expect(Material::whereRaw('LOWER(name) = ?', ['lavender'])->count())->toBe(1);
    });

    it('shows a cancel link on the create material page that returns to index', function () {
        $createUrl = route('materials.create');
        $indexUrl = route('materials.index');
        $formAction = route('materials.store');
        [$response, $crawler] = getPageCrawler($this->user, $createUrl);
        $cancelLink = $crawler->filter("form[action=\"$formAction\"] a[href=\"$indexUrl\"]");
        expect($cancelLink->count())->toBe(1);
        expect($cancelLink->text())->toContain('CANCEL');
    });

    it('saves pyramid tiers for a material', function () {
        $postUrl = route('materials.store');
        $redirectUrl = route('materials.index').'#material-1';
        $payload = materialPayload([
            'name' => 'Bergamot',
            'pyramid' => ['top'],
        ]);
        postAs($this->user, $postUrl, $payload)
            ->assertRedirect($redirectUrl);
        $this->assertDatabaseHas('materials', [
            'name' => 'Bergamot',
        ]);
        $material = Material::where('name', 'Bergamot')->first();
        expect($material->pyramid)->toBe(['top']);
    });

    it('shows pyramid tier inputs on the create form', function () {
        $createUrl = route('materials.create');
        [, $crawler] = getPageCrawler($this->user, $createUrl);
        assertInputs($crawler, 'pyramid[]', ['top', 'heart', 'base']);
    });

    it('creates a material with allowed taxonomy tags and IFRA percent', function () {
        $postUrl = route('materials.store');
        $payload = materialPayload([
            'families' => ['citrus'],
            'functions' => ['modifier'],
            'safety' => ['phototoxic', 'irritant'],
            'effects' => ['uplifting'],
            'ifra_max_pct' => 1.0,
        ]);
        $response = postAs($this->user, $postUrl, $payload);
        $material = Material::latest('id')->first();
        $redirectUrl = route('materials.index').'#material-'.$material->id;
        $response->assertRedirect($redirectUrl);
        // Checks the database
        $material = Material::where('name', 'Lavender')->first();
        expect($material)->not->toBeNull();
        expect($material->families)->toContain('citrus');
        expect($material->functions)->toContain('modifier');
        expect($material->safety)->toContain('phototoxic');
        expect($material->safety)->toContain('irritant');
        expect($material->effects)->toContain('uplifting');
        expect($material->ifra_max_pct)->toBeFloat()->toBe(1.0);
    });

    it('shows taxonomy fields on the create material form', function () {
        $createUrl = route('materials.create');
        [, $crawler] = getPageCrawler($this->user, $createUrl);
        assertInputs($crawler, 'families[]', config('materials.families'));
        assertInputs($crawler, 'functions[]', config('materials.functions'));
        assertInputs($crawler, 'safety[]', config('materials.safety'));
        assertInputs($crawler, 'effects[]', config('materials.effects'));
        expect($crawler->filter('input[name="ifra_max_pct"][type="number"]')->count())->toBe(1, 'Missing IFRA max % input');
    });

    it('shows the newly added family descriptors in the material create form', function () {
        $createUrl = route('materials.create');
        [$response, $crawler] = getPageCrawler($this->user, $createUrl);

        // helper to check each value
        $assertFamilyCheckbox = function (string $value) use ($crawler) {
            $selector = "input[type='checkbox'][name='families[]'][value='{$value}']";

            expect($crawler->filter($selector)->count())
                ->toBe(1, "Missing checkbox for family '{$value}'");
        };

        $assertFamilyCheckbox('creamy');
        $assertFamilyCheckbox('earthy');
        $assertFamilyCheckbox('powdery');
        $assertFamilyCheckbox('musky');
        $assertFamilyCheckbox('camphorous');
    });

    it('allows saving new family descriptors on material create', function () {
        $storeUrl = route('materials.store');
        $payload = materialPayload([
            'name' => 'my oil',
            'families' => ['creamy', 'camphorous', 'musky', 'earthy'],
        ]);
        $response = postAs($this->user, $storeUrl, $payload)
            ->assertRedirect();
        $material = Material::where('name', $payload['name'])->first();
        expect($material->families)->toEqualCanonicalizing(['creamy', 'camphorous', 'musky', 'earthy']);
    });

    test('shows success message when creating a new material', function () {
        // Post a new material
        $response = $this
            ->followingRedirects()
            ->post(route('materials.store'), materialPayload());

        $material = Material::latest()->first();

        // Get the HTML from the redirect
        $crawler = crawl($response);
        $materialContainer = $crawler->filter("div#material-{$material->id}");

        // Assert material created
        expect($materialContainer->text())->toContain("{$material->name} added");
    });
});

describe('editing materials', function () {
    it('shows the edit form for a material', function () {
        $material = makeMaterial();
        $editUrl = route('materials.edit', $material);
        getAs($this->user, $editUrl)
            ->assertOk()
            ->assertSee('Edit Material')
            ->assertSee('Lavender')
            ->assertSee('Lavandula Angustifolia');
    });

    test('shows success message when editing a material', function () {
        // Create material
        $material = makeMaterial();

        // Post and update with redirect
        $response = $this
            ->followingRedirects()
            ->patch(route('materials.update', $material), materialPayload(['notes' => '']));

        // Get HTML content for the material div
        $crawler = crawl($response);
        $materialContainer = $crawler->filter("div#material-{$material->id}");

        // Assert message
        expect($materialContainer->text())->toContain("{$material->name} updated");
    });
});

describe('deleting materials', function () {
    test('shows success message when deleting a material', function () {
        // Create material
        $material = makeMaterial();

        // delete a material through request with redirects
        $response = $this
            ->followingRedirects()
            ->delete(route('materials.destroy', $material));

        // Get HTML for index page
        $crawler = crawl($response);

        // Assert message
        expect($crawler->text())->toContain("{$material->name} deleted");
    });

    test('cannot be deleted if listed in a blend', function () {
        // Create material & blend + ingredient
        $material = makeMaterial();
        [$blend, $version] = makeBlendWithVersion($this->user, 'Blend');
        addIngredient($version, $material);

        // Send request from blends.edit  to materials.destroy + follow redirect
        $response = $this
            ->from(route('materials.edit', $material))
            ->followingRedirects()
            ->delete(route('materials.destroy', $material));

        // Get HTML from response
        $crawler = crawl($response);

        // Check material still exists in DB
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
        ]);

        // Assert response contains error message
        expect($crawler->text())->toContain("{$material->name} is in use and cannot be deleted");
    });
});

describe('materials index listing and navigation', function () {
    it('links each material on the index to its edit page', function () {
        $material = makeMaterial();
        $indexUrl = route('materials.index');
        $editUrl = route('materials.edit', $material);
        [$response, $crawler] = getPageCrawler($this->user, $indexUrl);
        $materialDiv = $crawler->filter("div#material-{$material->id}");
        $href = $materialDiv->filter('a')->attr('href');
        expect($materialDiv->count())->toBe(1);
        expect($href)->toBe($editUrl);
    });
    // ... rest of tests from materials index listing ...
});

describe('search and filtering', function () {
    it('shows a search input on the materials index', function () {
        getAs($this->user, 'materials')
            ->assertOk()
            ->assertSee('name="query"', false)
            ->assertSee('type="search"', false);
    });
});

describe('materials show page', function () {
    test('materials show highlights the assigned bottle for a blend ingredient', function () {
        // Create a material, 2 bottles for 1 ingredient & blend
        $lavender = makeMaterial();
        $lavenderBottle1 = makeBottle($lavender);
        [$blend, $version] = makeBlendWithVersion($this->user, 'Blend');
        $lavenderIngredient = addIngredient($version, $lavender, $lavenderBottle1->id);
        $lavenderBottle2 = makeBottle($lavender);

        // Get materials show page HTML with query parameter from blend ingredient ID
        [, $crawler] = getPageCrawler(
            $this->user,
            route('materials.show', $lavender).'?ingredient='.$lavenderIngredient->id
        );

        $bottle1 = $crawler->filter('#bottle-'.$lavenderBottle1->id);
        $bottle2 = $crawler->filter('#bottle-'.$lavenderBottle2->id);

        // Assert opacity
        expect($bottle1->attr('class'))->not->toContain('opacity-30');
        expect($bottle2->attr('class'))->toContain('opacity-50');
    });

    test('material show page informs user that creating a bottle will assign it to the ingredient', function () {
        // Create material & blend + add ingredient
        $material = makeMaterial();
        [$blend, $version] = makeBlendWithVersion($this->user, 'Blend');
        $ingredient = addIngredient($version, $material);

        // Get HTML from materials.show with blend ingredient context
        [, $crawler] = getPageCrawler($this->user,
            route('materials.show', $material).'?ingredient='.$ingredient->id);
        $text = $crawler->text();

        // Assert message is present
        expect($text)->toContain('Click "Add Bottle"');
        expect($text)->toContain($ingredient->material->name);
        expect($text)->toContain($blend->name);
    });

    test('it shows assigned bottle message when visiting material from ingredient with bottle', function () {
        // Create material & bottle
        $material = makeMaterial();
        $bottle = makeBottle($material);

        // Create blend & add ingredient
        [$blend, $version] = makeBlendWithVersion($this->user, 'Blend');

        $ingredient = addIngredient($version, $material, $bottle->id);
        $ingredient->refresh()->load('blendVersion.blend');

        [,$crawler] = getPageCrawler($this->user, route('materials.show', $material).'?ingredient='.$ingredient->id);
        $bottleText = $crawler->filter("div#bottle-$ingredient->bottle_id")->text();

        // Assert message is visible
        expect($bottleText)->toContain('This bottle is assigned to');
        expect($bottleText)->toContain($material->name);
        expect($bottleText)->toContain($blend->name);
    });
});
