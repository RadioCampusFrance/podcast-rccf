
function loop_reload(){
		$.ajax({
			type: "GET",
			async: false,
			timeout: 5000,
			url: "http://" + window.location.hostname + "/ws/?req=onair&d=" + new Date().getTime(),
			success:function(data)
			{
				var span = $("#titre-live");
				if (span != undefined) {
				if (data.type == null || data.type == "paulo")
				{
					span.html(data.titre+" - "+data.auteur);
				}
				else if (data.type == "emission")
				{
					span.html("émission " + data.titre);
				}
				else
				{
					span.html("-");
				}
			},
			error: function (textStatus, errorThrown) {
            }
		});
	return false;
}