var BankTransaction = {};

BankTransaction.reset_user_balance = function (event) {
  var user_id = $('input[name="snp_user_id"]').val();
  var url = "/app.php/snahp/test/handle/reset_user/?u=" + user_id;
  $.get(url).done((resp) => {
    location.reload();
  });
};
