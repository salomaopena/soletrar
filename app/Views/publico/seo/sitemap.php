<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= site_url() ?></loc></url>
  <?php foreach ($noticias as $n): ?>
  <url>
    <loc><?= esc($n->urlPublica()) ?></loc>
    <lastmod><?= esc(($n->data_publicacao)->format('Y-m-d')) ?></lastmod>
  </url>
  <?php endforeach ?>
</urlset>
