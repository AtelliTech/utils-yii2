<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Elements in HTML</title>
  <!-- Embed elements Elements via Web Component -->
  <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
</head>

<body>

  <elements-api id="docs" router="hash" layout="sidebar"></elements-api>
  <script>
    (async () => {
      const docs = document.getElementById('docs');
      const text = await fetch('<?php echo '<?= $yamlUri ?>'; ?>').then(res => res.text())
      docs.apiDescriptionDocument = text;
    })();
  </script>

</body>

</html>