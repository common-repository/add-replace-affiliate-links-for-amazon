<div class="wrap">
	<h1>Updated pages</h1>
	<?php 
		$pages = URALP_UpdAmazonPlugin::getUpdated();
		if (count($pages) > 0) {
			echo '<table class="widefat fixed"><thead><th>Page Name</th><th>Update time</th></thead><tbody>';
			foreach($pages as $rec) {
				
				$link = get_permalink( $rec->post_id );
				$title = get_the_title( $rec->post_id );
				echo "<tr>
					<td>
						<a target='_blank' href='" . esc_url($link) . "'>$title</a>
					</td>
					<td>
						" . esc_html($rec->time) . ";
					</td>
				</tr>";
			}
			echo "</tbody></table>";
		}
		else {
			echo '<b style="font-size: 16px;">No pages found.</b>';
		}
	?>	
</div>