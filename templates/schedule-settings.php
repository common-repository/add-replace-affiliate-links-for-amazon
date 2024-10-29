<div class="wrap">
	<h1>Settings for scheduled run</h1>
	<form id="upd-amazon-plugin-scheduler-form" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th>
					</th>
					<td>
						<input type="checkbox">
						<p class="description">
							Active
						</p>
					</td>
				</tr>
				<tr>
					<th>
						Enter New Tracking code
					</th>
					<td>
						<input type='text' id='new-id' name='new-id'></input>
					</td>
				</tr>
				<tr>
					<th>
					</th>
					<td>
						<?php submit_button('Update', 'primary', 'update-btn', false) ?>
						<p class="description">
							Update settings
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
			var data = {
				action: 'uralp_no_affiliate',
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-no_affiliate" ); ?>'
			};
			$("#loading").show();
			jQuery.post( ajaxurl, data, function(response) {
				$("#loading").hide();
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#new-id').val('');
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
			});
		});

		$('#no_follow-btn').click(function(e) {
			e.preventDefault();
			var data = {
				action: 'uralp_no_follow',
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-no_follow" ); ?>'
			};
			$("#loading").show();
			jQuery.post( ajaxurl, data, function(response) {
				$("#loading").hide();
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#new-id').val('');
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
			});
		});

		$('#expand-btn').click(function(e) {
			e.preventDefault();
			var data = {
				action: 'uralp_expand',
				'new-id': $('#new-id').val(),
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-expand" ); ?>'
			};
			$("#loading").show();
			jQuery.post( ajaxurl, data, function(response) {
				$("#loading").hide();
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#new-id').val('');
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
			});
		});

		$('#update-btn').click(function(e) {
			e.preventDefault();
			var data = {
				action: 'uralp_update',
				'new-id': $('#new-id').val(),
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-update" ); ?>'
			};
			$("#loading").show();
			jQuery.post( ajaxurl, data, function(response) {
				$("#loading").hide();
				var resp = jQuery.parseJSON(response);
				if (resp['success'] === 1) {
					jQuery('#response-msg').css({'color': 'green'});
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#new-id').val('');
				}
				else {
					jQuery('#response-msg').text(resp['msg']);
					jQuery('#response-msg').css({'color': 'red'});
				}
			});
		});

		$('#backup-btn').click(function(e) {
			e.preventDefault();
			var data = {
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-backup" ); ?>',
				action: 'uralp_backup'
			};
			$("#loading").show();
			jQuery.post( ajaxurl, data, function(response) {
				$("#loading").hide();
				var resp = jQuery.parseJSON(response);
				jQuery('#response-msg').css({'color': 'green'});
				jQuery('#response-msg').text(resp['msg']);
			});

		});

		$('#restore-btn').click(function(e) {
			e.preventDefault();
			var data = {
				security: '<?php echo wp_create_nonce( "upd-plugin-for-amazon-restore" ); ?>',
				action: 'uralp_restore'
			};
			$("#loading").show();
			jQuery.post( ajaxurl, data, function(response) {
				$("#loading").hide();
				var resp = jQuery.parseJSON(response);
				jQuery('#response-msg').css({'color': 'green'});
				jQuery('#response-msg').text(resp['msg']);
			});
		});
	});
</script>
