$(document).ready(function() {
  $(".editButtons input").each(function(){
    $(this).addClass("btn");
  });

  $(":submit").each(function(){
    $(this).addClass("btn");
  });

  $("table").each(function(){
    $(this).addClass("table table-striped table-bordered table-condensed");
  });

  $(".mw-parser-output table").each(function(){
    $(this).wrap('<div class="table-responsive-md"></div>');
  });

  $(".span4 .sectionLinks").hide();
});