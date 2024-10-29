<div class="wrap">
	<h1>Add & Replace Affiliate Links for Amazon</h1>
	<form id="upd-amazon-plugin-form" method="post">
		<table class="form-table">
			<tbody>
				<tr id="progress-row" style="display: none;">
					<th></th>
					<td>
						<h4>Current Progress</h4>
						<div class="meter">						
							<span class="progress-bar" style="width: 100%"></span>
						</div>
						<h4 id="progress-result"></h4>
						<b><h5>Please wait until the current operation is complete before running the next operation. If you have a big site, this can take some time.</h5></b>
					</td>
				</tr>
				<tr id="response-msg-row" style="display: none;">
					<th></th>
					<td>
						<span style="font-weight: 600; font-size: 14px;" id="response-msg"></span>
					</td>
				</tr>
				<tr>
					<th>
					</th>
					<td>
						<?php submit_button('Backup', 'primary', 'backup-btn', false) ?> 
						<p class="description">
							Backup pages that could be modified on update.
						</p>
					</td>
				</tr>
				<tr>
					<th>
					</th>
					<td>
						<?php submit_button('Restore', 'primary', 'restore-btn', false) ?>
						<p class="description">
							If something went wrong, it will help you to restore original state of post or page.
							<br>
						</p>
					</td>
				</tr>
				<tr>
					<th>
						Enter New Tracking code
					</th>
					<td>
						<?php $job = URALP_UpdAmazonPlugin::getLastKeyword();
							if ($job)
								echo "<input type='text' id='new-id' value='" . $job->keyword  . "' name='new-id'></input>";
							else
								echo "<input type='text' id='new-id' name='new-id'></input>";

							$checked = '';
							
							if ($job && wp_get_schedule('uralp_hook', array($job->keyword))) {
								$checked = 'checked';
							}
							echo '<input type="checkbox" ' . $checked . ' id="is-scheduled">Is Scheduled?</input>';

						?>
						
					</td>
				</tr>
				<tr>
					<th>
					</th>
					<td>
						<?php submit_button('Update', 'primary', 'update-btn', false) ?>
						<p class="description" >
							Add or replace the tracking code on all Amazon links among all pages/posts.
							<br>
						</p>
						<br><br>
					</td>
				</tr>
				<tr>
					<th>
					</th>
					<td>
						<?php submit_button('Expand', 'primary', 'expand-btn', false) ?>
						<p class="description">
							Expand shortended Amazon links to the full links the among all pages/posts.
							<br>
						</p>
					</td>
				</tr>
				<tr>
					<th>
					</th>
					<td>
						<?php submit_button('Add No follow', 'primary', 'no_follow-btn', false) ?>
						<p class="description">
							Add no-follow attribute to the all Amazon links among all pages/posts.
							<br>
						</p>
					</td>
				</tr>
				<tr>
					<th>
					</th>
					<td>
						<?php submit_button('Make non-affiliate', 'primary', 'no_affiliate-btn', false) ?>
						<p class="description">
							Remove "code" from all Amazon affiliate links among all pages/posts.
							<br>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>

<script>
	
	jQuery(document).ready(function($) {
		progressUpdate();
		setInterval(progressUpdate, 5000);

		function progressUpdate() {
			jQuery.post(
				ajaxurl,
				{
					action: 'uralp_get_progress',
					security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-get_progress" ); ?>'
				}, 
				function(response) {
					var resp = jQuery.parseJSON(response);
					if (resp.msg === null || resp.msg.percent === '100'){
						jQuery('#progress-row').hide();
						jQuery('#upd-amazon-plugin-form .button').prop('disabled', false);
					}
					else{
						jQuery('#progress-row').show();
						jQuery('#progress-row .progress-bar').width(resp.msg.percent + '%');
						jQuery('#progress-result').html('Progress: ' + resp.msg.percent + '% <br>Started at: ' + resp.msg.time);
						jQuery('#upd-amazon-plugin-form .button').prop('disabled', true);
					}
				});
		}

		$('#upd-amazon-plugin-form').submit(function(e) {
			e.preventDefault();
		});
		$('#no_affiliate-btn').click(function(e) {
			e.preventDefault();
			jQuery('#response-msg-row').hide();
			var data = {
				action: 'uralp_no_affiliate',
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-no_affiliate" ); ?>'
			};
			jQuery.post( ajaxurl, data, function(response) {
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
					
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
				jQuery('#response-msg-row').show();
			});
		});

		$('#no_follow-btn').click(function(e) {
			e.preventDefault();
			jQuery('#response-msg-row').hide();
			var data = {
				action: 'uralp_no_follow',
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-no_follow" ); ?>'
			};
			jQuery.post( ajaxurl, data, function(response) {
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
					
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
				jQuery('#response-msg-row').show();
			});
		});

		$('#expand-btn').click(function(e) {
			e.preventDefault();
			jQuery('#response-msg-row').hide();
			var data = {
				action: 'uralp_expand',
				'new-id': $('#new-id').val(),
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-expand" ); ?>'
			};
			jQuery.post( ajaxurl, data, function(response) {
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
					
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
				jQuery('#response-msg-row').show();
			});
		});

		$('#update-btn').click(function(e) {
			e.preventDefault();
			jQuery('#response-msg-row').hide();
			var data = {
				action: 'uralp_update',
				'new-id': jQuery('#new-id').val(),
				'is-scheduled': jQuery('#is-scheduled').is(':checked') ? '1' : '0',
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-update" ); ?>'
			};
			jQuery.post( ajaxurl, data, function(response) {
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
				jQuery('#response-msg-row').show();
			});
		});

		$('#backup-btn').click(function(e) {
			e.preventDefault();
			jQuery('#response-msg-row').hide();
			var data = {
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-backup" ); ?>',
				action: 'uralp_backup'
			};
			jQuery.post( ajaxurl, data, function(response) {
				var resp = jQuery.parseJSON(response);
				jQuery('#response-msg').css({'color': 'green'});
				jQuery('#response-msg').text(resp['msg']);
			});
			jQuery('#response-msg-row').show();
		});

		$('#restore-btn').click(function(e) {
			e.preventDefault();
			jQuery('#response-msg-row').hide();
			var data = {
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-restore" ); ?>',
				action: 'uralp_restore'
			};
			jQuery.post( ajaxurl, data, function(response) {
				var resp = jQuery.parseJSON(response);
				jQuery('#response-msg').css({'color': 'green'});
				jQuery('#response-msg').text(resp['msg']);
			});
			jQuery('#response-msg-row').show();
		});
	});
</script>
<style>
	.meter { 
		height: 20px;  /* Can be anything */
		position: relative;
		background: #555;
		-moz-border-radius: 25px;
		-webkit-border-radius: 25px;
		border-radius: 25px;
		padding: 5px;
		box-shadow: inset 0 -1px 1px rgba(255,255,255,0.3);
		width: 30%;
	}
	.meter > .progress-bar {
		display: block;
		height: 100%;
		border-top-right-radius: 8px;
		border-bottom-right-radius: 8px;
		border-top-left-radius: 20px;
		border-bottom-left-radius: 20px;
		background-color: rgb(43,194,83);
		background-image: linear-gradient(
			center bottom,
			rgb(43,194,83) 37%,
			rgb(84,240,84) 69%
		);
		box-shadow: 
			inset 0 2px 9px  rgba(255,255,255,0.3),
			inset 0 -2px 6px rgba(0,0,0,0.4);
		position: relative;
		overflow: hidden;
		}
</style>