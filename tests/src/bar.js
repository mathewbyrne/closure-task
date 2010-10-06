/**
 * More JS for testing, again this is fairly typical type JS for the purposes
 * of giving Closure something to compile. 
 */
(function (global, undefined) {

var Foo = global.Foo = function (bar) {
	this.foo = bar;
};

var bar = new Foo('baz');
	
})(window);
