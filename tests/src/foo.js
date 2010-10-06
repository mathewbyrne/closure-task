/**
 * Test javascript file. Doesn't do anything much, just somewhat typical JS
 * that may be found in a project.
 */
(function (global, undefined) {

var foo = 1,
 	bar = global.bar = function () {
		foo++;
		console.log(foo);
	};
	
})(window);
