<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<rss version="2.0"><channel>
  <title>Concurso Nacional de Soletração — Angola</title>
  <link><?= site_url() ?></link>
  <description>Notícias do concurso</description>
  <?php foreach ($noticias as $n): ?>
  <item>
    <title><?= esc($n->titulo) ?></title>
    <link><?= esc($n->urlPublica()) ?></link>
    <pubDate><?= esc(($n->data_publicacao)->format('r')) ?></pubDate>
  </item>
  <?php endforeach ?>
</channel></rss>
