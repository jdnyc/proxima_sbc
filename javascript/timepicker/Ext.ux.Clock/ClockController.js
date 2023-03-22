/**
 * @class Ext.ux.ClockController
 * @extends Ext.util.Observable
 */
/**
 * @constructor
 * @param Object
 */
Ext.ux.ClockController=function(config) {
	this.addEvents({
		/**
		 * @event render
		 * Fired before drawing anything to the canvas instance
		 * @param Ext.ux.ClockController
		 * @param Object The timestamp to render
		 */
		render: true,
		/**
		 * @event afterrender
		 * Fired after drawing the clock to the canvas instance
		 * @param Ext.ux.ClockController
		 * @param Object The timestamp to render
		 */
		afterrender: true
	});
	
	Ext.apply(this, config || {});
	Ext.ux.ClockController.superclass.constructor.apply(this, arguments);
	
	if(this.runTask) {
		this.task=Ext.TaskMgr.start({
			run:		this.render,
			scope:		this,
			interval:	this.taskInterval
		});
	}
	
	this.render();
}
Ext.extend(Ext.ux.ClockController, Ext.util.Observable, {
	/**
	 * @var Curly.Canvas The canvas instance for rendering operations
	 */
	canvas: null,
	/**
	 * @var integer X coordinate of the center of the clock
	 */
	x: 0,
	/**
	 * @var integer Y coordinate of the center of the clock
	 */
	y: 0,
	/**
	 * @var integer Size of the clock
	 */
	size: 30,
	/**
	 * @var integer Task interval
	 */
	taskInterval: 500,
	/**
	 * @var boolean Flag if the canvas object should be cleared before rendering
	 */
	autoClear: true,
	/**
	 * @var boolean Flag if this object should use a task runner to automatically
	 *  re-render the clock.
	 */
	runTask: true,
	/**
	 * @var Object Running task. See runTask.
	 */
	task: null,
	/**
	 * @var Object An object in the format {h: H, m: M, s: S} for the timestamp to render.
	 *  The default value is the current timestamp.
	 */
	date: null,
	/**
	 * @var Object Canvas state for the outer arc
	 */
	outerArcState: {
		strokeStyle:		'black',
		lineWidth:			1
	},
	/**
	 * @var Object Canvas state for the scale
	 */
	scaleState: {
		lineWidth:			1
	},
	/**
	 * @var integer Length of the scale values 3, 6, 9 and 12 Uhr in relation to the
	 *  overall size
	 */
	scaleInnerSize: 4,
	/**
	 * @var integer Length of the scale values 1, 2, 4, 5, 7, 8, 10 and 11
	 */
	scaleOuterSize: 8,
	/**
	 * @var Object Canvas state for drawing the hour indicator
	 */
	hourIndicatorState: {
		lineWidth:			3
	},
	/**
	 * @var Object Canvas state for drawing the minute indicator
	 */
	minuteIndicatorState: {
		lineWidth:			2
	},
	/**
	 * @var Object Canvas state for drawing the seconds indicator
	 */
	secondIndicatorState: {
		lineWidth:			1
	},
	/**
	 * @var integer Used for calculating the size of the hour indicator
	 */
	hourIndicatorShorten: 10,
	/**
	 * @var integer Used for calculating the size of the minute indicator
	 */
	minuteIndicatorShorten: 2,
	/**
	 * @var integer Used for calculating the size of the seconds indicator
	 */
	secondIndicatorShorten: 1,
	/**
	 * Renders this object
	 * 
	 * @return void
	 */
	render: function() {
		var date=this.date;
		if(date==null) {
			date=new Date();
		}
		if(date instanceof Date) {
			date={
				h: date.getHours(),
				m: date.getMinutes(),
				s: date.getSeconds()
			}
		}
		
		if(!this.fireEvent('render', this, date)) {
			return;
		}
		
		if(this.autoClear) {
			this.canvas.clear();
		}
		
		this.renderOuterArc();
		this.renderScale();
		this.renderIndicator(date);
		
		this.fireEvent('afterrender', this, date);
	},
	// private
	renderOuterArc: function() {
		this.canvas.
			overwriteState(this.outerArcState).
			draw(new Curly.ArcLine(this.x, this.y, this.size));
	},
	// private
	renderScale: function() {
		var add=Math.PI/6, angle=0, innerSize, ax, ay, path=this.canvas.path();
		this.canvas.overwriteState(this.scaleState).applyState();
		for(var i=0; i<12; i++) {
			angle+=add;
			if(i%3===2) {
				innerSize=this.size-this.size/this.scaleInnerSize;
			}
			else {
				innerSize=this.size-this.size/this.scaleOuterSize;
			}
			
			x=Math.sin(angle);
			y=Math.cos(angle);
			path.
				moveTo(innerSize*x+this.x, innerSize*y+this.y).
				lineTo(this.size*x+this.x, this.size*y+this.y);
		}
		path.
			moveTo(this.x, this.y).
			arc(1, 0, Math.PI*2, false).
			draw();
	},
	// private
	renderIndicator: function(date) {
		this.canvas.overwriteState(this.hourIndicatorState).applyState();
		this.indicator(this.size-this.hourIndicatorShorten, -Math.PI*((date.h%12+6)/6));
		
		this.canvas.overwriteState(this.minuteIndicatorState).applyState();
		this.indicator(this.size-this.minuteIndicatorShorten, -Math.PI*((date.m+30)/30));
		
		this.canvas.overwriteState(this.secondIndicatorState).applyState();
		this.indicator(this.size-this.secondIndicatorShorten, -Math.PI*((date.s+30)/30));
	},
	// private
	indicator: function(size, angle) {
		var x=Math.sin(angle);
		var y=Math.cos(angle);
		this.canvas.
			path(this.x, this.y).
			lineTo(size*x+this.x, size*y+this.y).
			draw();
	}
});
