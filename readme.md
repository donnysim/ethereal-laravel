# Ethereal Laravel

This package extends and changes some laravel functionalities.

> Minimum required laravel version is 5.4.
 
- [Model](#model)
 
## Model

This package provides extended laravel Model, named `Ethereal`. This model works a bit different in few use cases:

- fillable and guarded functionality is disabled

It's the only reasonable way to work with Eloquent and prevents the use of request()->all(). This also improves code readability where you don't have to guess what request 
data you'll receive as you'll have to specify it if you're going to fill it to the model.

- `columns` model field is available

This field is used to get dirty columns for model save or update, so you can have as many custom attributes in there as you want. If the column is empty (default), all 
attributes are held as possible table columns.

- models have validation

Methods like `valid` and `validOrFail` are provided to validate the model. `fullyValid` is present if you want to validate the model and all it's relations. This uses 
laravel's validator so after any validation action you can call `validator` to get the last used validator. Model validation rules are determined by `validationRules` method - 
sometimes rules change depending on model existence, to determine model existence you can use `$this->exists` model field.

- `relationships` model field is available

`relationships` field can be used to write all relations that can be auto filled by `fill` method. In addition, `extendedRelations` property is available and enabled by default 
to provide automatic relation packing - you can pass array and it will be converted into model or collection depending on relation type. Otherwise `setRelation` can be used to 
achieve the same behavior.

> Note: unsupported relation types or values will be added as is. Added models and collection with models will only be checked for type validity, array elements in collection 
will not be converted into models.
