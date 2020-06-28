<?php if(isset($inertia)) : ?>
<div id="app" data-page="<?= htmlspecialchars(Data::encode($inertia, 'json'), ENT_QUOTES, 'UTF-8') ?>"></div>
<?php endif ?>