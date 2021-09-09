var Formatter = {};
Formatter.number_with_commas = function (x) {
  return x.toLocaleString();
};

Formatter.number_as_tokens = function (x) {
  return "$" + x.toLocaleString();
};

var ShopTransaction = {};

ShopTransaction.initiate_buy = function (event) {
  $("#mod_confirm_shop_btn").removeClass("d-none");
  $target = $(event.target);
  var j = JSON.parse($target[0].dataset.json);
  var id = j.id;
  var quantity = $("#shop_buy_quantity_" + id).val();
  if (quantity <= 0) {
    return false;
  }
  j["quantity"] = quantity;
  j["total"] = quantity * j.price;
  j["total_as_tokens"] = Formatter.number_as_tokens(j["total"]);
  $("#mod_confirm_shop").modal("show");
  total = Formatter.number_with_commas(quantity);
  $container = $("#mod_confirm_shop_body");
  $container.html(`<p style="font-size:1.5em;">
    You are about to purchase <b>${j["quantity"]} ${j["display_name"]}</b>.
    This will cost you ${j["total_as_tokens"]}.
    Please confirm this transaction.
    </p>`);
};

ShopTransaction.confirm_buy = function (event) {
  $container = $("#mod_confirm_shop_body");
  $container.html(`<div class="mx-auto" style="width: 30px;">
      <div class="spinner-border text-secondary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
      </div>`);
  var j = JSON.parse($target[0].dataset.json);
  j.quantity = $("#shop_buy_quantity_" + j.id).val();
  var url = `/app.php/snahp/economy/dashboard/buy_product/?pcid=${j["id"]}&quantity=${j["quantity"]}`;
  $.get(url).done((resp) => {
    var status = resp.status;
    if (status == 1) {
      location.reload();
    } else {
      $("#mod_confirm_shop_btn").addClass("d-none");
      $container.html(`<p style="font-size:1.6em;">
                We were unable process your request.<br><br>
                <b>Reason: ${resp["reason"]}</b>
                </p>`);
    }
  });
};
