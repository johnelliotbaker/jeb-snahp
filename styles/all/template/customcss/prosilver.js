// Prevent BBCodeBanner redirection to home page
$("input#keywords").click((e) => {
  e.stopPropagation();
});
