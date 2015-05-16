

$(document).ready(function() {
  $('div.accordion > div').hide();  
  $('div.accordion > h2').click(function() {
    $(this).next('div').slideToggle('fast').siblings('div:visible').slideUp('fast');
  });
});


/*
$(document).ready(function(){
  var sectionNo = 0;
  $('h2').each(function(){
    $(this).attr('id', 'section_head_' + (++sectionNo));
  });
  
  $('h2').css('cursor', 'pointer').click(function(){
    $('.section').hide();
    $(this).next().show();
    setCookie('curSectionHeadId', $(this).attr('id'));
  });
  
  var curSectionHeadId;
  if(curSectionHeadId = getCookie('curSectionHeadId')){
     $('#' + curSectionHeadId).click();
  }
});
*/

function setCookie( name, value, expires, path, domain, secure ) 
{
  // set time, it's in milliseconds
  var today = new Date();
  today.setTime( today.getTime() );

  /*
  if the expires variable is set, make the correct 
  expires time, the current script below will set 
  it for x number of days, to make it for hours, 
  delete * 24, for minutes, delete * 60 * 24
  */
  if(expires){
    expires = expires * 1000 * 60 * 60 * 24;
  }
  var expires_date = new Date( today.getTime() + (expires) );
  document.cookie = name + "=" +escape( value ) +
    ( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) + 
    ( ( path ) ? ";path=" + path : "" ) + 
    ( ( domain ) ? ";domain=" + domain : "" ) +
    ( ( secure ) ? ";secure" : "" );
}

function getCookie( check_name ) {
  // first we'll split this cookie up into name/value pairs
  // note: document.cookie only returns name=value, not the other components
  var a_all_cookies = document.cookie.split( ';' );
  var a_temp_cookie = '';
  var cookie_name = '';
  var cookie_value = '';
  var b_cookie_found = false; // set boolean t/f default f
  
  for ( i = 0; i < a_all_cookies.length; i++ )
  {
    // now we'll split apart each name=value pair
    a_temp_cookie = a_all_cookies[i].split( '=' );
    
    
    // and trim left/right whitespace while we're at it
    cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');
  
    // if the extracted name matches passed check_name
    if ( cookie_name == check_name )
    {
      b_cookie_found = true;
      // we need to handle case where cookie has no value but exists (no = sign, that is):
      if ( a_temp_cookie.length > 1 )
      {
        cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+$/g, '') );
      }
      // note that in cases where cookie is initialized but no value, null is returned
      return cookie_value;
      break;
    }
    a_temp_cookie = null;
    cookie_name = '';
  }
  if ( !b_cookie_found )
  {
    return null;
  }
}  