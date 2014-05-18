(function($) {
	$(document).ready(function() {
	
		$("#wpmsrc-theme").change(function(e){
			var theme = $(e.target).val();
			console.log(theme);
		});
	
	});
})(jQuery);