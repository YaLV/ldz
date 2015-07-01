<ul>
  {menuItems}
</ul>
<div class='clear'></div>

<div class='filters leftFilters'>
  <div class="filter_header search_header">
    <p>Meklēt</p>
  </div>
  <div class='filter_selection'>
    <form method='post' action='/biedri' class='filter'>
      <input type='hidden' name='filter' value='1' />
      <table class='filter_table'>
        {filters}
        <tr>
          <td colspan='2'><button class='pull-left'>Meklēt</button>&nbsp;<a class='btn btn-mini btn-warning pull-right' href='/biedri?reset'>Dzēst filtrus</a></td>
        </tr>
      </table>
    </form>
  </div>
</div>