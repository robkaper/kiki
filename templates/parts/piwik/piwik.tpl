{if $kiki.config.piwikHost}
  {if $kiki.config.piwikSiteId}
<!-- Piwik -->
<script type="text/javascript">
var _paq = _paq || [];
_paq.push(["trackPageView"]);
_paq.push(["enableLinkTracking"]);
var u=(("https:" == document.location.protocol) ? "https" : "http") + "://{$kiki.config.piwikHost}/piwik/";
var id={$kiki.config.piwikSiteId};

(function() {
  _paq.push(["setTrackerUrl", u+"piwik.php"]);
  _paq.push(["setSiteId", id]);
  var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];
  g.type="text/javascript"; g.defer=true; g.async=true; g.src=u+"piwik.js";
  s.parentNode.insertBefore(g,s);
} )();
</script>
<noscript><p><img src="//{$kiki.config.piwikHost}/piwik/piwik.php?idsite={$kiki.config.piwikSiteId}&amp;rec=1" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Code -->
  {/if}
{/if}
                              