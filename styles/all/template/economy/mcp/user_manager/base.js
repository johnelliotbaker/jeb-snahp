var Formatter = {};
Formatter.number_with_commas = function (x) {
  return parseInt(x).toLocaleString();
};

Formatter.number_as_tokens = function (x) {
  return "$" + this.number_with_commas(x);
};

var EconomyUserAccount = {};

EconomyUserAccount.get_inventory_table_body_element = function () {
  return $("#user_inventory_table_body");
};

EconomyUserAccount.fill_user_balance = function (balance) {
  $("#user_balance").val(balance);
};

EconomyUserAccount.empty_user_inventory_table = function () {
  this.get_inventory_table_body_element().empty();
};

EconomyUserAccount.generate_inventory_row = function (inv) {
  inv.price_formatted = Formatter.number_as_tokens(parseInt(inv.price));
  var row = `<tr>
  <td class="pt-2">${inv["display_name"]}</td>
  <td class="pt-2">${inv["description"]}</td>
  <td class="pt-2">${inv["quantity"]} / ${inv["max_per_user"]}</td>
  <td class="pt-2">${inv["value"]} ${inv["unit"]}</td>
  <td class="pt-2">${inv["price_formatted"]}</td>
  <td class="pt-1">
    <input id="inventory_quantity_${inv["id"]}" 
    style="font-size: 1.1em; height:20px; width:60px; text-align:right;" 
    class="form-control form-control-sm w-10" value=${inv["quantity"]} type="number" min="0" max="${inv["max_per_user"]}" value="0"/>
  </td>
  <td class="pt-1 text-center">
    <button 
      data-id="${inv["id"]}"
      onclick="EconomyUserInventory.save_inventory_item(event);"
      style="font-size:10px; padding:1px 5px 1px 5px;"
      type="button" class="btn btn-success">Save</button>
  </td>
</tr>
`;
  return row;
};

EconomyUserAccount.fill_user_inventory_table = function (a_inventory) {
  $tbody = this.get_inventory_table_body_element();
  this.empty_user_inventory_table();
  for (inventory of a_inventory) {
    let row = this.generate_inventory_row(inventory);
    $tbody.append(row);
  }
};

EconomyUserAccount.request_account_info = function (event) {
  var user_id = UserSelection.data.user_id;
  if (!user_id) {
    return false;
  }
  var url = "/app.php/snahp/economy/uam/get_user_account/?u=" + user_id;
  $.get(url).done((resp) => {
    let balance = resp["balance"];
    let inventory = resp["inventory"];
    this.fill_user_balance(balance);
    this.fill_user_inventory_table(inventory);
  });
};
