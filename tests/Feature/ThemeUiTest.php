<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows a theme toggle with system, light and dark options', function () {
    $indexUrl = route('materials.index');
    [, $crawler] = getPageCrawler($this->user, $indexUrl);

    $toggleButton = $crawler->filter('button[onclick="toggleTheme()"]');

    // There are 2 butttons, 1 for burger menu 1 for desktop menu
    expect($toggleButton->count())->toBe(2);
    expect($toggleButton->filter('img[class*="dark:hidden"][src*="sun-icon.svg"]')->count())->toBe(2);
    expect($toggleButton->filter('img[class*="dark:block"][src*="moon-icon.svg"]')->count())->toBe(2);
});
