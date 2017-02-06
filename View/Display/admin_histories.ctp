<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Ventes du webmarket</h3>
        </div>
        <div class="box-body">

          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Acheteur</th>
                <th>ID de la vente</th>
                <th>Mode de paiement</th>
                <th>Montant du paiement</th>
                <th>Vendeur</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</section>
<script type="text/javascript">
$(document).ready(function() {
  $('table').DataTable({
    "paging": true,
    "lengthChange": false,
    "searching": false,
    "ordering": false,
    "info": false,
    "autoWidth": false,
    'searching': true,
    "bProcessing": true,
    "bServerSide": true,
    "sAjaxSource": "<?= $this->Html->url(array('controller' => 'display', 'action' => 'get_histories', 'plugin' => 'PlayerMarket', 'admin' => true)) ?>",
    "aoColumns": [
        {mData:"User.pseudo"},
        {mData:"PurchaseHistory.selling_id"},
        {mData:"PurchaseHistory.mode"},
        {mData:"PurchaseHistory.price"},
        {mData:"Seller.pseudo"},
        {mData:"PurchaseHistory.created"}
    ],
  });
});
</script>
