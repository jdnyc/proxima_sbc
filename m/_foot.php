<?php if($index!=true){?>
		<div class="footer" style='font-family:"EBSFont-L";'><?=_text('MN02203')?></div>
	</div><!-- content -->

	<!-- top button -->
	<a data-role="button" id="toTop" data-inline="true" class="totop" style="visibility:hidden;"><img src="./img/arrow_top.png" class="arrow_top"/>&nbsp;TOP</a>

	<div data-role="panel" id="left-panel" data-theme="d">
		<p>Category</p>
		<div id="list_categories" class=""></div>
		<a href="#" data-rel="close" data-role="button"  data-icon="delete" data-iconpos="right"><?=_text('MN00031')?></a>
	</div><!-- /panel --> 
</div><!-- page -->

<?php }?>
<script type="text/javascript">
	jQuery(function($){

		$('#nav_header').children('li').on('click', function(){
			$(this).children('a').css('color','rgba(0, 0, 0, 0.0)');
		});

		$('#nav_header').children('li').on('mouseover', function(){
			$(this).children('a').css('color','rgba(0, 0, 0, 0.0)');
		});

		if( '<?=$loc?>' == 'mov_view' )
		{
			$('#nav_header').children('li').children('a').css('color','#646464');
		}
		$('#alert_y').on('click',function(){
			$('#sure').popup('close');
		})
	});
</script>

</body>
</html>