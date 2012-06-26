<body>
{include 'parts/header'}
{include 'parts/nav'}
{include 'parts/aside-admin'}
{include 'parts/aside'}

<script type="text/javascript">
function displayHtml() {
HTMLcode = document.getElementById('source').innerHTML;
console.log(HTMLcode);
//document.getElementById('display').textContent = HTMLCode;
return HTMLcode;
}
</script>

<div onclick="displayHtml();">
<button onclick="document.execCommand('bold',false,null);">Bold</button>
<button onclick="document.execCommand('italic',false,null);">Italic</button>
<div contenteditable id="source" onkeyup="javascript:displayHtml();">
Try making some of this text bold or even italic...
</div>
</div>

<div id="cw" class="twosides"><div id="content">
  <h1>{$title}</h1>
  {$content}
</div></div>

{include 'parts/footer'}

<div id="jsonUpdate"></div>
</body>
