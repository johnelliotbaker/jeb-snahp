var Formatter = {};
Formatter.number_with_commas = function(x)
{
    return x.toLocaleString();
}

Formatter.number_as_tokens = function(x)
{
  return '$' + x.toLocaleString();
}

var BankTransaction = {};

BankTransaction.reset_user_balance = function(event)
{
    var user_id = $('input[name="snp_user_id"]').val();
    var url = '/app.php/snahp/economy/user_dashboard/reset_user/?u=' + user_id;
    $.get(url).done((resp)=>{
        location.reload();
    });
}

BankTransaction.initiate_exchange = function(event)
{
    $('#mod_confirm_btn').removeClass('d-none');
    $target = $(event.target);
    var json = JSON.parse($target[0].dataset.json);
    var id = json.id;
    var rate = json.sell_rate;
    var amount = $('#bank_exchange_amount_' + id).val();
    if (amount <= 0) { return false; }
    $('#mod_confirm').modal('show');
    var total = rate * amount;
    // total = Formatter.number_with_commas(total);
    total = Formatter.number_as_tokens(total);
    $container = $('#mod_confirm_body');
    $container.html(`<p style="font-size:1.5em;">
    You are about to sell ` + amount + ` Invitation Point and receive   ` + total + `.
    This action is final.
    </p>`);
}

BankTransaction.confirm_exchange = function(event)
{
    $container = $('#mod_confirm_body');
    $container.html(`<div class="mx-auto" style="width: 30px;">
        <div class="spinner-border text-secondary" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        </div>`);
    var j = JSON.parse($target[0].dataset.json);
    j.amount = $('#bank_exchange_amount_' + j.id).val();
    j.dir = 'sell';
    var url = `/app.php/snahp/economy/user_dashboard/exchange/?id=${j['id']}&dir=${j['dir']}&amount=${j['amount']}`
    console.log(url);
    $.get(url).done((resp)=>{
        var status = resp.status;
        console.log(resp);
        if (status==1)
        {
            location.reload();
        }
        else
        {
            $('#mod_confirm_btn').addClass('d-none');
            $container.html(`<p style="font-size:1.6em;">
                Unfortunately, we were unable process your request.<br><br>
                <b>Reason: ${resp['reason']}</b>
                </p>`);
        }
    });
}

BankTransaction.get_exchange_row_data = function(id)
{
    return JSON.parse($('#bank_exchange_row_data_' + id)[0].dataset.json);
}

BankTransaction.update_exchange_row_total = function(event)
{
    $target = $(event.target);
    var id = $target[0].dataset.id;
    var data = BankTransaction.get_exchange_row_data(id);
    var rate = data.sell_rate;
    var amount = $('#bank_exchange_amount_' + id).val();
    $total = $('#bank_exchange_total_' + id);
    $total.text(data.buy_unit + Formatter.number_with_commas(amount*rate));
}
