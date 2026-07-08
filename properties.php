<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/properties.php';
require_once __DIR__ . '/includes/properties-page.php';
require_once __DIR__ . '/includes/settings.php';

$filters = [
    'keyword'         => trim($_GET['keyword'] ?? ''),
    'location'        => trim($_GET['location'] ?? ''),
    'property_type'   => trim($_GET['property_type'] ?? ''),
    'listing_purpose' => trim($_GET['listing_purpose'] ?? ''),
    'status'          => trim($_GET['status'] ?? ''),
    'min_price'       => trim($_GET['min_price'] ?? ''),
    'max_price'       => trim($_GET['max_price'] ?? ''),
];

$category = strtolower(trim($_GET['category'] ?? 'all'));
$allowedCategories = ['all', 'plots', 'villas', 'off-plan'];
if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

$sort = $_GET['sort'] ?? 'newest';
$allowedSorts = ['newest', 'oldest', 'title'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

$perPage = 8;
$page    = max(1, (int) ($_GET['page'] ?? 1));

/**
 * @param array<string, string> $overrides
 */
function properties_page_url(array $overrides = []): string
{
    global $filters, $category, $sort, $page;

    $params = [];
    foreach ($filters as $key => $value) {
        if ($value !== '') {
            $params[$key] = $value;
        }
    }
    if ($category !== 'all') {
        $params['category'] = $category;
    }
    if ($sort !== 'newest') {
        $params['sort'] = $sort;
    }
    if ($page > 1) {
        $params['page'] = (string) $page;
    }

    $params = array_merge($params, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        }
    }

    $query = http_build_query($params);

    return 'properties.php' . ($query !== '' ? '?' . $query : '');
}

try {
    $properties      = property_search($filters, $category, $sort);
    $locations       = property_distinct_values('location_name');
    $propertyTypes   = property_distinct_values('property_type');
    $listingPurposes = property_distinct_values('listing_purpose');
    $statuses        = property_status_filter_options();
    $minPriceOptions = property_price_filter_options('min');
    $maxPriceOptions = property_price_filter_options('max');
    $rentalsAvailable = property_rentals_available();
    if (!$rentalsAvailable) {
        $listingPurposes = array_values(array_filter(
            $listingPurposes,
            static fn(string $p): bool => strtolower($p) !== 'rent'
        ));
    }
} catch (Throwable $e) {
    $properties      = [];
    $locations       = [];
    $propertyTypes   = [];
    $listingPurposes = [];
    $statuses        = [];
    $minPriceOptions = [];
    $maxPriceOptions = [];
    $dbError         = true;
}

$totalCount  = count($properties);
$totalPages  = max(1, (int) ceil($totalCount / $perPage));
$page        = min($page, $totalPages);
$offset      = ($page - 1) * $perPage;
$properties  = array_slice($properties, $offset, $perPage);
$lotsByProperty = property_lots_by_property_ids(array_column($properties, 'id'));

$hasFilters = array_filter($filters) !== [] || $category !== 'all';

$tabs = [
    'all'      => 'All Properties',
    'plots'    => 'Plots',
    'villas'   => 'Villas',
    'off-plan' => 'Off-Plan',
];

try {
    $propsHero = properties_page_content(settings_properties_page());
} catch (Throwable $e) {
    $propsHero = properties_page_content([]);
}

$propsHeroImageUrl = properties_page_asset_url($propsHero['image']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Properties | UZ Estates</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/header-nav.css" />
  <link rel="stylesheet" href="css/properties.css?v=<?php echo (int) filemtime(__DIR__ . '/css/properties.css'); ?>" />
  <link rel="stylesheet" href="css/page-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/page-animate.css'); ?>" />
  <link rel="stylesheet" href="css/properties-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/properties-animate.css'); ?>" />
  <style>.props-hero-bg { background-image: url('<?php echo e($propsHeroImageUrl); ?>'); }</style>
</head>
<body class="properties-body">

<?php require __DIR__ . '/includes/site-header.php'; ?>

<main class="properties-page">

  <section class="props-hero" data-props-hero>
    <div class="props-hero-bg" data-props-hero-bg aria-hidden="true" role="img" aria-label="<?php echo e($propsHero['image_alt']); ?>"></div>
    <div class="props-hero-overlay" aria-hidden="true"></div>
    <div class="props-hero-inner" data-reveal-group>
      <p class="kicker" data-reveal="fade-up"><?php echo e($propsHero['kicker']); ?></p>
      <h1 data-reveal="fade-up"><?php echo e($propsHero['title']); ?></h1>
      <p class="props-hero-lead" data-reveal="fade-up"><?php echo e($propsHero['lead']); ?></p>
    </div>
  </section>

  <section class="props-finder" id="finder">
    <div class="props-finder-shell">
      <h2 class="props-finder-title">Find a property</h2>

      <form class="props-search-form" method="get" action="properties.php" data-props-search-form>
        <?php if ($category !== 'all'): ?>
          <input type="hidden" name="category" value="<?php echo e($category); ?>" />
        <?php endif; ?>
        <?php if ($sort !== 'newest'): ?>
          <input type="hidden" name="sort" value="<?php echo e($sort); ?>" />
        <?php endif; ?>

        <button type="button" class="props-filter-toggle" data-props-filter-toggle aria-expanded="false" aria-controls="propsFilterPanel">
          <span class="props-filter-toggle-label">Show filters</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>

        <div class="props-search-grid" id="propsFilterPanel" data-props-filter-panel>
          <label class="props-field props-field-keyword">
            <span class="props-label">Keyword</span>
            <span class="props-input-wrap">
              <svg class="props-field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
              <input type="text" id="keyword" name="keyword" placeholder="Search title, location, price…"
                     value="<?php echo e($filters['keyword']); ?>" />
            </span>
          </label>

          <label class="props-field">
            <span class="props-label">Location</span>
            <select id="location" name="location">
              <option value="">All locations</option>
              <?php foreach ($locations as $location): ?>
                <option value="<?php echo e($location); ?>" <?php echo $filters['location'] === $location ? 'selected' : ''; ?>>
                  <?php echo e($location); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="props-field">
            <span class="props-label">Type</span>
            <select id="property_type" name="property_type">
              <option value="">All types</option>
              <?php foreach ($propertyTypes as $type): ?>
                <option value="<?php echo e($type); ?>" <?php echo $filters['property_type'] === $type ? 'selected' : ''; ?>>
                  <?php echo e($type); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="props-field">
            <span class="props-label">Purpose</span>
            <select id="listing_purpose" name="listing_purpose">
              <option value="">All purposes</option>
              <?php foreach ($listingPurposes as $purpose): ?>
                <option value="<?php echo e($purpose); ?>" <?php echo $filters['listing_purpose'] === $purpose ? 'selected' : ''; ?>>
                  <?php echo e($purpose); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="props-field">
            <span class="props-label">Status</span>
            <select id="status" name="status">
              <option value="">All statuses</option>
              <?php foreach ($statuses as $status): ?>
                <option value="<?php echo e($status); ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>>
                  <?php echo e($status); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="props-field">
            <span class="props-label">Min price</span>
            <select id="min_price" name="min_price">
              <option value="">Any min</option>
              <?php foreach ($minPriceOptions as $option): ?>
                <option value="<?php echo e($option['value']); ?>" <?php echo $filters['min_price'] === $option['value'] ? 'selected' : ''; ?>>
                  <?php echo e($option['label']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="props-field">
            <span class="props-label">Max price</span>
            <select id="max_price" name="max_price">
              <option value="">Any max</option>
              <?php foreach ($maxPriceOptions as $option): ?>
                <option value="<?php echo e($option['value']); ?>" <?php echo $filters['max_price'] === $option['value'] ? 'selected' : ''; ?>>
                  <?php echo e($option['label']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <div class="props-search-actions">
            <button type="submit" class="props-search-btn">Search Properties</button>
            <?php if ($hasFilters): ?>
              <a href="properties.php" class="props-search-clear">Clear filters</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </section>

  <section class="props-listing" id="properties">
    <div class="props-listing-shell">
      <div class="props-toolbar" data-reveal="fade-up">
        <div class="props-toolbar-row">
          <nav class="props-tabs" aria-label="Property categories" data-props-tabs>
            <span class="props-tabs-indicator" data-props-tabs-indicator aria-hidden="true"></span>
            <?php foreach ($tabs as $slug => $label): ?>
              <a href="<?php echo e(properties_page_url(['category' => $slug === 'all' ? '' : $slug, 'page' => ''])); ?>"
                 class="props-tab<?php echo $category === $slug ? ' is-active' : ''; ?>">
                <?php echo e($label); ?>
              </a>
            <?php endforeach; ?>
          </nav>

          <div class="props-toolbar-controls">
            <form class="props-sort-form" method="get" action="properties.php" id="propsSortForm">
              <?php foreach ($filters as $key => $value): ?>
                <?php if ($value !== ''): ?>
                  <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>" />
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if ($category !== 'all'): ?>
                <input type="hidden" name="category" value="<?php echo e($category); ?>" />
              <?php endif; ?>
              <label class="props-sort-label">
                Sort by:
                <select name="sort" id="propsSort" class="props-sort-select">
                  <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                  <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest first</option>
                  <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title A–Z</option>
                </select>
              </label>
            </form>

            <div class="props-view-toggle" role="group" aria-label="View mode">
              <button type="button" class="props-view-btn is-active" data-view="grid" aria-label="Grid view" aria-pressed="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg>
              </button>
              <button type="button" class="props-view-btn" data-view="list" aria-label="List view" aria-pressed="false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
              </button>
            </div>
          </div>
        </div>

        <p class="props-count"><span id="propsCount"><?php echo (int) $totalCount; ?></span> propert<?php echo $totalCount === 1 ? 'y' : 'ies'; ?> found</p>
      </div>

      <?php if (!empty($dbError)): ?>
        <div class="props-empty" data-reveal="fade-up">
          <p>Properties could not be loaded. Please run the database migration first.</p>
        </div>
      <?php elseif ($totalCount === 0): ?>
        <div class="props-empty" data-reveal="fade-up">
          <p>No properties found. Try adjusting your search or filters.</p>
          <a href="properties.php" class="props-empty-link">View all properties</a>
        </div>
      <?php else: ?>
        <div class="props-grid" id="propsGrid" data-reveal-group>
          <?php foreach ($properties as $property): ?>
            <?php
              $detailUrl     = 'property-details.php?id=' . (int) $property['id'];
              $badges        = property_card_badges($property);
              $cardTitle     = property_card_title($property);
              $propertyLots  = $lotsByProperty[(int) $property['id']] ?? [];
              $priceLine     = property_card_price_for_property($property, $propertyLots);
              $typeLine      = property_card_type_label($property);
              $statLine      = property_card_stat_for_property($property, $propertyLots);
              $galleryImages = property_card_gallery_images($property);
              if ($galleryImages === []) {
                  $fallback = property_media_public_url($property['card_image'] ?? null);
                  if ($fallback) {
                      $galleryImages = [$fallback];
                  }
              }
              $hasGalleryNav = count($galleryImages) > 1;
            ?>
            <article class="props-card" data-reveal="fade-in">
              <div class="props-card-shell">
                <a href="<?php echo e($detailUrl); ?>" class="props-card-hit" aria-label="View <?php echo e($cardTitle); ?>">
                  <div class="props-card-media">
                    <?php if ($galleryImages !== []): ?>
                      <div class="props-card-gallery" data-props-gallery>
                        <div class="props-card-slides">
                          <?php foreach ($galleryImages as $index => $galleryUrl): ?>
                            <img
                              class="props-card-slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
                              src="<?php echo e($galleryUrl); ?>"
                              alt="<?php echo e($cardTitle); ?>"
                              loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                            />
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php else: ?>
                      <div class="props-card-placeholder" aria-hidden="true"></div>
                    <?php endif; ?>

                    <?php if ($badges !== []): ?>
                      <div class="props-card-badges">
                        <?php foreach ($badges as $badge): ?>
                          <span class="props-badge props-badge--<?php echo e($badge['kind']); ?> props-badge--<?php echo e($badge['variant']); ?>">
                            <?php echo e($badge['label']); ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="props-card-body">
                    <h3><?php echo e($cardTitle); ?></h3>

                    <?php if ($priceLine !== ''): ?>
                      <p class="props-card-price"><?php echo e($priceLine); ?></p>
                    <?php endif; ?>

                    <?php if ($typeLine !== ''): ?>
                      <p class="props-card-type"><?php echo e($typeLine); ?></p>
                    <?php endif; ?>

                    <?php if ($statLine !== ''): ?>
                      <ul class="props-card-stat">
                        <li>
                          <svg class="props-card-stat-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M3 9h18M9 3v18"/></svg>
                          <span><?php echo e($statLine); ?></span>
                        </li>
                      </ul>
                    <?php endif; ?>
                  </div>
                </a>

                <?php if ($hasGalleryNav): ?>
                  <div class="props-card-controls" data-props-gallery-controls>
                    <button type="button" class="props-card-nav props-card-nav--prev" aria-label="Previous image">‹</button>
                    <button type="button" class="props-card-nav props-card-nav--next" aria-label="Next image">›</button>
                    <span class="props-card-counter" data-props-counter aria-live="polite">1 / <?php echo count($galleryImages); ?></span>
                  </div>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($totalCount > 0): ?>
          <nav class="props-pagination" aria-label="Properties pagination" data-reveal="fade-up">
            <?php if ($page > 1): ?>
              <a href="<?php echo e(properties_page_url(['page' => (string) ($page - 1)])); ?>" class="props-page-btn" aria-label="Previous page">‹</a>
            <?php else: ?>
              <span class="props-page-btn is-disabled" aria-hidden="true">‹</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="<?php echo e(properties_page_url(['page' => (string) $i])); ?>"
                 class="props-page-num<?php echo $i === $page ? ' is-active' : ''; ?>"
                 <?php echo $i === $page ? 'aria-current="page"' : ''; ?>>
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <a href="<?php echo e(properties_page_url(['page' => (string) ($page + 1)])); ?>" class="props-page-btn" aria-label="Next page">›</a>
            <?php else: ?>
              <span class="props-page-btn is-disabled" aria-hidden="true">›</span>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="props-cta" data-reveal="fade-up">
    <div class="props-cta-shell" data-reveal-group>
      <div class="props-cta-icon" data-reveal="scale-up" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/><circle cx="12" cy="11" r="2"/></svg>
      </div>
      <div class="props-cta-copy" data-reveal="fade-up">
        <h2>Can't find what you're looking for?</h2>
            <p>We'll help you find the right property.</p>
      </div>
      <a href="contact.php" class="props-cta-btn" data-reveal="fade-up">
        <span class="props-cta-btn-label">Get in Touch</span>
        <span class="props-cta-btn-arrow" aria-hidden="true">→</span>
      </a>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<script src="js/script.js"></script>
<script src="js/properties.js?v=<?php echo (int) filemtime(__DIR__ . '/js/properties.js'); ?>"></script>
<script src="js/properties-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/properties-animate.js'); ?>"></script>
<script src="js/page-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/page-animate.js'); ?>"></script>
</body>
</html>
