<div id="auth-panel" class="container">
	<div class="col-md-4 col-md-offset-4" style="margin-top: 10em;">
		<div class="panel panel-default">
			<div class="panel-heading text-center">
				<h3 style="margin: 0;"><?php echo $layout['panelTitle']; ?></h3>
			</div>
			<div class="panel-body" style="padding-bottom: 0;">
				<?php if ( isset($authLayout['flash']) ) echo $authLayout['flash']; ?>
				<?php if ( isset($layout['content']) ) echo $layout['content']; ?>
			</div>
		</div>
	</div>
</div>
<style type="text/css">body { background-color: #f9f9f9; }</style>