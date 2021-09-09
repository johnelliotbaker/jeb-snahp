var Favfilter = {};
Favfilter.link_checkboxes = function () {
  $p = $(".favfilter.parent");
  $p.each((i) => {
    $elem = $($p[i]);
    $elem.change((e) => {
      $elem = $(e.target);
      $b = $elem.prop("checked");
      $card = $elem.closest(".card");
      $cb = $card.find('input[type="checkbox"]');
      $cb.each((i) => {
        $($cb[i]).prop("checked", $b);
      });
    });
  });
};
$(function () {
  Favfilter.wrapper = $("#favfilter_wrapper");
  Favfilter.height = Favfilter.wrapper.height();
  Favfilter.link_checkboxes();
});
