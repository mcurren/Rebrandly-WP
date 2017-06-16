(function($){

	$('#rebrandly-domain-list').hide();

	var optionApiKey = $('#url-shortener-input-field').val();
	var optionDomainId = $('#branded-url-input-field').val();
	// console.log(optionApiKey);
	
	$('#get-rebrandly-domains').on('click', function() {
		$.ajax({
      url: 'https://api.rebrandly.com/v1/domains?orderBy=createdAt&orderDir=desc&offset=0&limit=100&active=true&type=user',
      beforeSend: function(xhr) {
				xhr.setRequestHeader("apikey", optionApiKey)
      }, success: function(data){
      	// alert(data);
      	$('#current-domain-output').hide();
      	var result = jQuery.parseJSON( JSON.stringify( data ) );
				for (var key in result) {
			    // skip loop if the property is from prototype
			    if (!result.hasOwnProperty(key)) continue;

			    var isSelected = '';
			    var obj = result[key];
			    if (optionDomainId === obj['id']) isSelected = ' selected';
			    $('#rebrandly-domain-list').append( '<option value="' + obj['id'] + '"' + isSelected + '>' + obj['fullName'] + '</option>' ).show();
				}
      	// console.log(result);
	      // $('#rebrandly-domain-list').append( result );
      }
		})
	});

	$("#rebrandly-domain-list").change(function() {
  	var optionDomainId = $(this).find('option:selected').val();
  	$('#branded-url-input-field').val(optionDomainId);
	});

})( jQuery );