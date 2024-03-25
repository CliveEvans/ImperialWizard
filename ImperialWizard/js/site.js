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

  $(".span4 .sectionLinks").hide();
});