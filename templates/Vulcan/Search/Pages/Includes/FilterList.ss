<ul class="filtersWrapper">
    <% loop $FilterMap %>
        <li>
            <div class="field checkbox">
                <label><input type="checkbox" class="searchFilters" name="searchFilters[]" value="$Key"<% if $IsActive %>checked<% end_if %>/> $Value</label>
            </div>
        </li>
    <% end_loop %>
</ul>