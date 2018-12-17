$(document).ready(function(){


	// Add smooth scrolling to these links
  	$(".navbar a, footer a[href='#page']").on('click', function(event) {

   	// Make sure this.hash has a value before overriding default behavior
  	if (this.hash !== "") {
    	// Prevent default anchor click behavior
   		event.preventDefault();
    	var hash = this.hash;
    	// The optional number (900) specifies the number of milliseconds it takes to scroll to the specified area
    	$('html, body').animate({
      	scrollTop: $(hash).offset().top
    	}, 900, function(){
      	// Add hash (#) to URL when done scrolling (default click behavior)
      	window.location.hash = hash;
      	});
    }
  	});


  	



	$.fn.visible = function(partial) {

		var $t            = $(this),
			$w            = $(window),
			viewTop       = $w.scrollTop(),
			viewBottom    = viewTop + $w.height(),
			_top          = $t.offset().top,
			_bottom       = _top + $t.height(),
			compareTop    = partial === true ? _bottom : _top,
			compareBottom = partial === true ? _top : _bottom;

		//return ((compareBottom <= viewBottom) && (compareTop >= viewTop));
		return (compareBottom <= viewBottom);

	};

	var allMods = $(".slide-anim");

	function updateScrollAnims(c) {
		allMods.each(function(i, el) {
			var el = $(el);
			if (el.visible(true)) {
				el.addClass(c); 
			} 
		});
	}

	updateScrollAnims("already-visible");
	$(window).scroll(function(event) {
		updateScrollAnims("slide-in");
	});

})

