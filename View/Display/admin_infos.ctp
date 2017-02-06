<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Gérer le webmarket</h3>
        </div>
        <div class="box-body">

          <?php
          if ($state)
            echo '<a class="btn btn-block btn-danger" href="'.$this->Html->url(array('action' => 'disable')).'">Désactiver le webmarket</a>';
          else
            echo '<a class="btn btn-block btn-success" href="'.$this->Html->url(array('action' => 'enable')).'">Activer le webmarket</a>';
          ?>

        </div>
      </div>
    </div>
  </div>
</section>
