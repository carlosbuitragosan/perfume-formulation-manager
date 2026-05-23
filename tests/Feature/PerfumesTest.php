<?php

use App\Models\User;

// -----------------------
// Create user for all tests
// -----------------------
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('allows navigating to the perfume creation form from a blend version', function () {
    // Create blend + version
    [$blend, $version] = makeBlendWithVersion($this->user, 'Test Blend');

    // Get HTML for blends show page and filter for version div
    [$response, $crawler] = getPageCrawler($this->user, route('blends.show', $blend));
    $versionDiv = $crawler->filter("div#version-{$version->id}");

    // Assert "Create Perfume" button exists
    expect($versionDiv->selectLink('Create Perfume')->count())->toBe(1);
    expect($versionDiv->selectLink('Create Perfume')->link()->getUri())->toBe(route('blend-versions.perfumes.create', $version));

    // Assert button links to perfume creation page for version
    getAs($this->user, route('blend-versions.perfumes.create', $version))
        ->assertOk();
});
