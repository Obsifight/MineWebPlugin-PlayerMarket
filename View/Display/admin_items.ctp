<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Items minecraft</h3>
        </div>
        <div class="box-body">

          <form method="post">

            <?php foreach ($items as $k => $item) { ?>
              <input type="hidden" name="items[<?= $k ?>][id]" value="<?= $item['MinecraftItem']['id'] ?>">
              <div class="form-group">
                <label>ID</label>
                <input type="text" class="form-control" name="items[<?= $k ?>][minecraft_id]" value="<?= $item['MinecraftItem']['minecraft_id'] ?>">
              </div>
              <div class="form-group">
                <label>Nom</label>
                <input type="text" class="form-control" name="items[<?= $k ?>][name]" value="<?= $item['MinecraftItem']['name'] ?>">
              </div>
              <div class="form-group">
                <label>Nom de texture</label>
                <input type="text" class="form-control" name="items[<?= $k ?>][texture_name]" value="<?= $item['MinecraftItem']['texture_name'] ?>">
              </div>
              <div class="form-group">
                <label>Nom de traduction</label>
                <input type="text" class="form-control" name="items[<?= $k ?>][unlocalized_name]" value="<?= $item['MinecraftItem']['unlocalized_name'] ?>">
              </div>
              <div class="form-group">
                <label>Nom traduit</label>
                <input type="text" class="form-control" name="items[<?= $k ?>][translated_name]" value="<?= $item['MinecraftItem']['translated_name'] ?>">
              </div>
              <hr>
            <?php } ?>

            <input type="hidden" name="data[_Token][key]" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-success">Enregistrer</button>

          </form>

        </div>
      </div>
    </div>
  </div>
</section>
