<script>
  $(document).ready(function(){ $("#listItems").AddClickableTable(); });  
</script>
<div id='listItemsContainer'>
  <table id='headerList'>
    <tr>{headerItems}</tr>
  </table>
</div>
<div class='clear' style='height:20px;'></div>
<table id='listItems'>
  {listData}
</table>
<div class='clear' style='height:40px;'></div>
<div class='pagination'>{pages}</div>