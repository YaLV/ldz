<div class='filters export'>
  <div class='filter_header export_header'>
    <p>Eksports</p>
  </div>
  <div class='filter_selection'>
    <form method='get' action='/xlsv?create&type=v'>
      <input type='hidden' name='export' value='1' />
      <input type='hidden' name='create' value='1' />
      <input type='hidden' name='type' value='v' />
      <input type='hidden' name='displayFields' id='displayFields' value='' data-submit='gatherExportables' />
      <table class='filter_table'>
        {export}
        <tr>
          <td colspan='2'><button class='pull-left'>Eksportēt</button>&nbsp;<a class='btn btn-mini btn-warning pull-right' style='cursor:pointer;' onclick="$('.exportable').prop('checked',true);">Visi lauki</a></td>
        </tr>
      </table>
    </form>
  </div>
</div>

<script>
  $(document).ready(function(){
    $('form[method=get]').submit(function(){
      $("[data-submit]").each(function(){
        eval($(this).attr('data-submit')+"()");
      });
    });
  });

  function gatherExportables() {
    var exportFields = new Array();
    $('.exportable:checked').each(function(){
      exportFields.push($(this).val());
    });
    $('#displayFields').val(exportFields.join(','));
  }
</script>