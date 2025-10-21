# Custom Fields - Quick Summary

## ✅ Apa yang Sudah Dibuat

### 1. **Database & Model**
- ✅ Migration: Add `custom_fields` JSON column to contacts
- ✅ Contact Model: Updated with custom fields support
  - `custom_fields` in $fillable
  - Cast to array
  - Helper methods: `getCustomField()`, `setCustomField()`, `getTemplateData()`

### 2. **Import System**
- ✅ CSV Import: Auto-detect custom columns
- ✅ Standard columns: name, phone, email, company, notes
- ✅ Custom columns: Semua kolom lain otomatis jadi custom fields
- ✅ Sample CSV included dengan 10 contacts + 6 custom fields

### 3. **Template Integration**
- ✅ Custom fields otomatis available di template
- ✅ Format: `{custom_field_name}`
- ✅ Auto-replace saat render message

### 4. **Documentation**
- ✅ Complete guide (CUSTOM_FIELDS_GUIDE.md)
- ✅ Setup command
- ✅ Sample data
- ✅ Real-world examples

---

## 🚀 Quick Start (3 Steps)

### Step 1: Setup
```bash
# Create setup command
php artisan make:command SetupCustomFields

# Copy code dari artifact, then run:
php artisan setup:custom-fields
```

### Step 2: Import Data
1. Download sample CSV: `contacts_with_custom_fields.csv`
2. Go to Contact List
3. Click "Import CSV"
4. Upload file
5. ✅ Check "Has header row"
6. Click Import

**Result:** 10 contacts dengan custom fields:
- `discount` (10, 5, 15, 20, etc.)
- `product_interest` (Laptop, Smartphone, etc.)
- `vip_status` (Gold, Silver, Platinum)
- `city` (Jakarta, Bandung, etc.)
- `birthday` (1990-01-15, etc.)
- `member_since` (2020-01-01, etc.)

### Step 3: Create Template
```
Hi {name}! 🎉

Thank you for being our {vip_status} member from {city}!

🎁 Your exclusive discount: {discount}%
📦 Recommended: {product_interest}
🎂 Birthday: {birthday}
⭐ Member since: {member_since}

Use code: VIP{discount} for extra benefits!

Best regards,
{company}
```

**Preview Result:**
```
Hi John Doe! 🎉

Thank you for being our Gold member from Jakarta!

🎁 Your exclusive discount: 10%
📦 Recommended: Laptop
🎂 Birthday: 1990-01-15
⭐ Member since: 2020-01-01

Use code: VIP10 for extra benefits!

Best regards,
PT Example Indonesia
```

---

## 📁 Files Updated/Created

### ✅ Migration
```
database/migrations/xxxx_add_custom_fields_to_contacts_table.php
```

### ✅ Model
```
app/Models/Contact.php
+ custom_fields in $fillable
+ custom_fields cast to array
+ getCustomField() method
+ setCustomField() method
+ getTemplateData() method (includes custom fields)
```

### ✅ Controller
```
app/Http/Controllers/ContactController.php
+ CSV import auto-detects custom columns
+ store() accepts custom_fields array
+ update() accepts custom_fields array
+ downloadTemplate() with custom field examples
```

### ✅ Documentation
```
CUSTOM_FIELDS_GUIDE.md        - Complete guide
CUSTOM_FIELDS_SUMMARY.md      - This file
contacts_with_custom_fields.csv - Sample data
```

### ✅ Command
```
app/Console/Commands/SetupCustomFields.php
```

---

## 🎯 How It Works

### **Import Flow:**

1. **CSV Upload**
```csv
name,phone,email,company,discount,city,product
John,0812...,john@...,PT ABC,10,Jakarta,Laptop
```

2. **System Detects Columns**
   - Standard: name, phone, email, company
   - Custom: discount, city, product

3. **Data Stored**
```json
{
  "name": "John",
  "phone": "0812...",
  "custom_fields": {
    "discount": "10",
    "city": "Jakarta", 
    "product": "Laptop"
  }
}
```

4. **Template Usage**
```
Hi {name} from {city}!
Get {discount}% off on {product}!
```

5. **Rendered Message**
```
Hi John from Jakarta!
Get 10% off on Laptop!
```

---

## 💡 Use Cases

### **E-Commerce**
```csv
name,phone,last_order,total_spent,loyalty_points,favorite_category
John,0812...,2025-10-01,1500000,450,Electronics
```

**Template:**
```
Hi {name}! Last order: {last_order}
Points: {loyalty_points}
New {favorite_category} items!
```

### **Gym/Fitness**
```csv
name,phone,membership_type,expiry_date,trainer,goal
Sarah,0823...,Premium,2025-12-31,Mike,Weight Loss
```

**Template:**
```
Hi {name}! Membership: {membership_type}
Expires: {expiry_date}
Trainer: {trainer} | Goal: {goal}
```

### **Real Estate**
```csv
name,phone,budget,location_pref,property_type,urgency
David,0834...,500000000,South Jakarta,Apartment,High
```

**Template:**
```
Hi {name}! New {property_type} in {location_pref}
Budget fit: Rp {budget} | Urgency: {urgency}
```

### **Education**
```csv
name,phone,course,batch,start_date,progress
Emma,0845...,Web Dev,10,2025-11-01,75
```

**Template:**
```
Hi {name}! Course: {course} Batch {batch}
Start: {start_date} | Progress: {progress}%
```

---

## 📊 Custom Fields vs Standard Fields

### **Standard Fields** (Built-in columns)
- `name` ✅ Required
- `phone` ✅ Required
- `email` 
- `company`
- `notes`
- `first_name` (auto-extracted from name)
- `last_name` (auto-extracted from name)
- `date` (current date)
- `time` (current time)

### **Custom Fields** (JSON column)
- ✅ Unlimited fields
- ✅ Any field name (lowercase, underscore)
- ✅ No table alteration needed
- ✅ Flexible per contact
- ✅ Import from CSV automatically

**Example Data:**
```json
{
  "name": "John Doe",
  "phone": "08123456789",
  "email": "john@example.com",
  "custom_fields": {
    "discount": "10",
    "city": "Jakarta",
    "vip_status": "Gold",
    "product": "Laptop",
    "birthday": "1990-01-15"
  }
}
```

---

## 🔧 Advanced Usage

### **Access in Code:**
```php
$contact = Contact::find(1);

// Get custom field
$discount = $contact->getCustomField('discount');
$city = $contact->getCustomField('city', 'Unknown');

// Set custom field
$contact->setCustomField('discount', '15');
$contact->save();

// Get all template data (includes custom fields)
$data = $contact->getTemplateData();
// Returns: ['name' => 'John', 'discount' => '10', ...]
```

### **Search by Custom Field:**
```php
// PostgreSQL JSON query
$contacts = Contact::whereJsonContains('custom_fields->city', 'Jakarta')->get();

$vipContacts = Contact::whereJsonContains('custom_fields->vip_status', 'Platinum')->get();
```

### **Bulk Update:**
```php
Contact::where('contact_list_id', $listId)
    ->get()
    ->each(function ($contact) {
        $contact->setCustomField('promo_2025', 'active');
        $contact->save();
    });
```

---

## ✅ Testing Checklist

### Setup:
- [ ] Migration run successfully
- [ ] `custom_fields` column exists in contacts table
- [ ] Contact model updated
- [ ] ContactController updated

### CSV Import:
- [ ] Can upload CSV with custom columns
- [ ] Custom columns detected automatically
- [ ] Data saved in `custom_fields` JSON
- [ ] Standard columns saved in standard fields

### Templates:
- [ ] Can use `{custom_field}` in templates
- [ ] Preview shows custom field values
- [ ] Template render replaces custom fields correctly

### UI:
- [ ] Contact list shows custom fields (optional)
- [ ] Contact detail shows custom fields
- [ ] Can edit custom fields via form (optional)

---

## 🎨 Best Practices

### **✅ Do:**
- Use lowercase field names: `discount`, `city`
- Use underscores for spaces: `product_interest`
- Keep field names descriptive: `member_since`, `last_purchase`
- Validate values when accepting from forms
- Use consistent field names across imports

### **❌ Don't:**
- Use capitals: `Discount`, `CITY`
- Use spaces: `product interest`
- Use hyphens: `product-interest`
- Use dots: `product.interest`
- Store very long values (keep under 500 chars)

---

## 🐛 Troubleshooting

### **Custom fields not saving?**
```bash
# Check column exists
php artisan tinker
Schema::hasColumn('contacts', 'custom_fields')

# Check model
# Ensure 'custom_fields' in $fillable
# Ensure cast to 'array' in $casts
```

### **CSV import not detecting custom columns?**
- Ensure CSV has header row
- Check "Has header row" checkbox
- Column names must be lowercase
- No spaces in column names
- File encoding must be UTF-8

### **Custom fields not in template?**
```php
// Debug
$contact = Contact::find(1);
dd($contact->getTemplateData());

// Should include custom fields
```

---

## 📞 Quick Commands

```bash
# Setup custom fields
php artisan setup:custom-fields

# Check if column exists
php artisan tinker
Schema::hasColumn('contacts', 'custom_fields')

# Test contact data
php artisan tinker
Contact::first()->getTemplateData()

# Export contacts with custom fields
# (implement in ContactController)
```

---

## 🎯 Summary

**Custom Fields allows:**
✅ Import unlimited custom data via CSV  
✅ No database schema changes needed  
✅ Use in templates with `{field_name}`  
✅ Flexible per-contact data  
✅ Easy integration with blast campaigns  

**Perfect for:**
- Personalized marketing
- Customer segmentation  
- Dynamic promotions
- Membership management
- Any business with unique data needs

**Next Steps:**
1. Run setup command
2. Import sample CSV
3. Create template with custom variables
4. Test preview & send!

---

**Need specific implementation for your use case? Just ask!** 🚀