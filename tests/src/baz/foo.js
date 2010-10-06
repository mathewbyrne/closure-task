/**
 * This file is to test the handling of nested files and file lists.
 */
(function () {

var i = 1;

$(function () {
	$('.links').click(function () {
		alert('You clicked ' + $(this).text() + ' ' + i++);
	});
});
	
})(jQuery);
