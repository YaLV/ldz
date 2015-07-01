<form method='post' id='editField' action='{action}'>
  <input type='hidden' name='field' value='{fieldName}'>
  <input type='hidden' name='idField' value='{idFieldName}'>
  <input type='text' name='{fieldName}' value='{fieldValue}'>
  <br /><br />
  <button class='pull-right'>Saglabāt</button>
  <div class='clear'></div>
</form>
<script>
  $(function() {
    $('#editField').updatePost(); 
  });
</script>