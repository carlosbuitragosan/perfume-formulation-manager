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
