# User Docs

## Installation

*Please refer to the developer documentation.*

### Permission

The bundle adds a new permission, `plugin_valantic_dataquality_config`, which is enforced to view/edit the bundle's configuration screens. Any logged in user may view the scores of a Data Object.

## Configuration

There are two configuration screens, Constraints and Meta, both of which can be found in the Marketing menu in Pimcore's backend, each one prefixed with "Data Quality".

### Constraints

Constraints refer to the validation rules enforced on an object's attribute and is vertically divided into two sections. On the left-hand side, you can find a list of which attributes are configured and once an attribute is clicked on, the right-hand side gives you a list of which constraints are configured for this attribute.

To configure constraints for a new attribute, you may use the left-hand side "Add" button in the toolbar to add it to the list. The form will only show attributes that have not yet been configured.

Once the new attribute is in the left-hand side list, you can click on it to bring up the right-hand side view of constraints for this attribute. (Naturally, the same applies if you want tot configure an existing attribute.) Once the right-hand side list is open, you can add a new constraint by clicking on the corresponding "Add" button.

When you choose a constraint from the dropdown, a parameter hint will be displayed. Let's have a look at the constraint `Choice` in detail:

> Choice requires the following parameters:
>
> `{"choices":[]}`
>
> Optional parameters:
>
> `{"callback":[],"max":0,"min":0,"multiple":false}`
>
> The default parameters is: `choices`

And let's say your valid choices are `apples, oranges`.

You can now configure this constraint and its support for a default parameter using:

```json
["apples", "oranges"]
```

If you want to be explicit and not use the default parameter support:

```json
{"choices": ["apples", "oranges"]}
```

The latter syntax is also needed if you want to configure any of the optional parameters, e.g.:

```json
{"choices": ["apples", "oranges"], "min": 1, "max":  2, "multiple": true}
```

To edit a constraint, double-click the corresponding row.

To delete an attribute or a constraint, you can right-click the entry and choose "Delete" from the menu.

### Meta

Meta configuration allows you to specify:

- which languages are validated for localized attributes (default: none)
- threshold score for "green"
- threshold score for "orange"

To configure a new class, you may use the "Add" button. Only unconfigured classes ca be added. Multiple locales can be selected by using Shift/Cmd.

To edit a configuration entry, double-click the corresponding row.

To delete a configuration entry, right-click the entry and choose "Delete" from the menu. 

## View

To view the data quality for an object, open the object. If there is a configuration for any attribute of this object, a tab "Data Quality" will be shown which includes a colored score, representing the overall score of this object. The color (like all other colors) depend on the color thresholds in the meta configuration.

To see a more detailed view, click on the tab and you can see a row for every attribute. For every active language (see meta configuration), there is a column. The attribute cell is colored depending on the overall score for this attribute whereas the "Score" columns (for non-localized and localized attributes) shows the individual score.

The score is the number of passing constraints divided by the number of constraints.
