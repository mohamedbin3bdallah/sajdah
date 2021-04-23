<script>
	$(document).ready(function(){
		$("#unreadNTs").click(function(){
			$.ajax({
				type: 'POST',
				url: '<?php echo base_url(); ?>home/read',
				data: {
				},
				success: function (response) { $( "#unreadNTsC" ).hide(); }
			});
		});
	});
</script>