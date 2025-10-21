# Blast Campaign System - Update Summary

## ✅ Yang Sudah Diupdate

### **1. Migration - Add Missing Columns**
File: `database/migrations/xxxx_add_columns_to_blast_schedules.php`

**Kolom baru di `blast_schedules`:**
- ✅ `description` (text) - Campaign description
- ✅ `target_type` (enum) - Target selection type
- ✅ `target_ids` (json) - Array of target IDs
- ✅ `schedule_type` (enum) - immediate/scheduled
- ✅ `pending_count` (integer) - Pending messages count
- ✅ Update status enum - tambah 'pending'

**Update `blast_messages` table:**
- ✅ Rename dari `sent_messages` → `blast_messages`
- ✅ `recipient_name` (string) - Contact name
- ✅ `waha_response` (json) - WAHA API response
- ✅ Update status enum - tambah 'queued'

### **2. Model Updates**

**BlastSchedule.php - New Methods:**
- ✅ `getTargetContacts()` - Get contacts based on target_type
- ✅ `calculateStats()` - Update statistics
- ✅ `isPending()` - Check if pending
- ✅ `canBeCancelled()` - Check if can be cancelled
- ✅ `markAsCancelled()` - Mark as cancelled
- ✅ `getProgressAttribute()` - Get progress percentage
- ✅ `getStatusColorAttribute()` - Badge color
- ✅ `getStatusLabelAttribute()` - Status label

**SentMessage.php (Blast Messages):**
- ✅ Support both table names (sent_messages/blast_messages)
- ✅ `markAsSent()` - Mark as sent with WAHA response
- ✅ `markAsFailed()` - Mark as failed
- ✅ `markAsQueued()` - Mark as queued
- ✅ `markAsDelivered()` - Mark as delivered
- ✅ `markAsRead()` - Mark as read
- ✅ Status color & icon attributes
- ✅ Scopes for filtering

### **3. Controller**
File: `app/Http/Controllers/BlastScheduleController.php`

**Methods:**
- ✅ `index()` - List campaigns with stats & filter
- ✅ `create()` - Form create campaign
- ✅ `store()` - Save campaign & create messages
- ✅ `show()` - View campaign details
- ✅ `cancel()` - Cancel campaign
- ✅ `report()` - Campaign report & analytics
- ✅ `preview()` - Preview message with data

---

## 🎯 New Features

### **1. Target Selection**
Sekarang support 4 tipe target:

```php
'all' => All contacts dari user
'contact_list' => Specific contact lists (multiple)
'contact_group' => Specific tags/groups (multiple)
'selected_contacts' => Specific selected contacts
```

**Example:**
```php
// All contacts
'target_type' => 'all',
'target_ids' => null

// Multiple contact lists
'target_type' => 'contact_list',
'target_ids' => [1, 2, 3]

// Multiple groups/tags
'target_type' => 'contact_group',
'target_ids' => [5, 7]

// Selected contacts
'target_type' => 'selected_contacts',
'target_ids' => [10, 15, 20, 25]
```

### **2. Schedule Type**
```php
'immediate' => Send now (dispatch to queue)
'scheduled' => Send at specific time
```

### **3. Status Flow**
```
draft → pending → processing → completed
                            → failed
                            → cancelled
```

### **4. Message Status**
```
pending → queued → sent → delivered → read
                       → failed
```

---

## 🚀 Setup Instructions

### Step 1: Run Migration
```bash
php artisan make:migration add_columns_to_blast_schedules
```

Copy code dari artifact `add_columns_to_blast_schedules.php`, then:

```bash
php artisan migrate
```

### Step 2: Update Models

Replace/Update:
- `app/Models/BlastSchedule.php`
- `app/Models/SentMessage.php`

### Step 3: Create Controller

Create:
```bash
php artisan make:controller BlastScheduleController
```

Copy code dari artifact.

### Step 4: Add Routes

Add to `routes/web.php`:
```php
Route::middleware(['auth'])->group(function () {
    // Blast Campaigns
    Route::prefix('blasts')->name('blasts.')->group(function () {
        Route::get('/', [App\Http\Controllers\BlastScheduleController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\BlastScheduleController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\BlastScheduleController::class, 'store'])->name('store');
        Route::get('/{blast}', [App\Http\Controllers\BlastScheduleController::class, 'show'])->name('show');
        Route::post('/{blast}/cancel', [App\Http\Controllers\BlastScheduleController::class, 'cancel'])->name('cancel');
        Route::get('/{blast}/report', [App\Http\Controllers\BlastScheduleController::class, 'report'])->name('report');
        Route::post('/preview', [App\Http\Controllers\BlastScheduleController::class, 'preview'])->name('preview');
    });
});
```

---

## 📊 Database Structure

### `blast_schedules` Table:
```sql
id, user_id, account_id, template_id, contact_list_id,
name, description, 
target_type, target_ids, 
schedule_type, scheduled_at,
status, 
total_recipients, sent_count, failed_count, pending_count,
rate_limit_delay,
started_at, completed_at, error_message,
created_at, updated_at
```

### `blast_messages` Table:
```sql
id, blast_schedule_id, contact_id,
phone_number, recipient_name, message_content,
status, waha_message_id, waha_response, error_message,
sent_at, created_at, updated_at
```

---

## 🎨 Usage Examples

### **Example 1: Create Immediate Blast to All Contacts**
```php
BlastSchedule::create([
    'user_id' => auth()->id(),
    'account_id' => 1,
    'template_id' => 5,
    'name' => 'Flash Sale Announcement',
    'description' => 'Send flash sale promo to all customers',
    'target_type' => 'all',
    'target_ids' => null,
    'schedule_type' => 'immediate',
    'status' => 'pending',
]);
```

### **Example 2: Schedule Blast to Specific Lists**
```php
BlastSchedule::create([
    'user_id' => auth()->id(),
    'account_id' => 1,
    'template_id' => 3,
    'name' => 'Birthday Wishes',
    'target_type' => 'contact_list',
    'target_ids' => [2, 5, 8], // Multiple contact lists
    'schedule_type' => 'scheduled',
    'scheduled_at' => '2025-12-25 09:00:00',
    'status' => 'scheduled',
]);
```

### **Example 3: Send to VIP Group Only**
```php
BlastSchedule::create([
    'user_id' => auth()->id(),
    'account_id' => 1,
    'template_id' => 10,
    'name' => 'VIP Exclusive Offer',
    'target_type' => 'contact_group',
    'target_ids' => [3], // VIP tag ID
    'schedule_type' => 'immediate',
    'status' => 'pending',
]);
```

---

## 🔄 Backward Compatibility

### **Existing Data:**
- `contact_list_id` tetap berfungsi
- Jika `target_type` NULL → default ke 'contact_list'
- Jika `target_ids` NULL → use `contact_list_id`

### **Migration Safe:**
- No data loss
- Existing campaigns tetap work
- New features optional

---

## 🧪 Testing Checklist

### Database:
- [ ] Migration runs successfully
- [ ] All new columns exists
- [ ] Table renamed (sent_messages → blast_messages)
- [ ] Constraints updated

### Models:
- [ ] BlastSchedule methods working
- [ ] SentMessage methods working
- [ ] Relationships working
- [ ] Attributes (progress, success_rate) calculated correctly

### Controller:
- [ ] Can create campaign
- [ ] Target selection works (all/list/group/selected)
- [ ] Schedule works (immediate/scheduled)
- [ ] Messages created correctly
- [ ] Statistics calculated
- [ ] Can cancel campaign
- [ ] Preview works

---

## 📝 Next Steps

After setup complete:

1. ✅ **Create Views**
   - blasts/index.blade.php
   - blasts/create.blade.php
   - blasts/show.blade.php
   - blasts/report.blade.php

2. ✅ **Queue Jobs**
   - ProcessBlastCampaign Job
   - SendBlastMessage Job

3. ✅ **WAHA Integration**
   - Send message via WAHA API
   - Handle webhook responses
   - Update message status

4. ✅ **Scheduler**
   - Check scheduled campaigns
   - Auto-trigger at scheduled time

---

## 🐛 Troubleshooting

### Migration Error: Column already exists
```bash
# Check existing columns
php artisan tinker
Schema::getColumnListing('blast_schedules')

# Skip migration if columns exist
# Or modify migration to check before adding
```

### Table name conflict
```bash
# If sent_messages table exists
# The migration will rename it to blast_messages
# Update SentMessage model to use new table name
```

### Model not found
```bash
# Create alias if needed
// In SentMessage.php
class SentMessage extends Model
{
    protected $table = 'blast_messages'; // Use new table name
}

// Or create BlastMessage alias
class BlastMessage extends SentMessage
{
    // Alias class
}
```

---

## ✅ Summary

**What's New:**
✅ Multi-target selection (all, lists, groups, selected)  
✅ Schedule type (immediate/scheduled)  
✅ Better statistics tracking  
✅ Progress monitoring  
✅ Enhanced status management  
✅ WAHA API response tracking  

**Backward Compatible:**
✅ Existing campaigns still work  
✅ contact_list_id fallback  
✅ No breaking changes  

**Ready for:**
- Views creation
- Queue implementation
- WAHA integration
- Production deployment

---

Siap lanjut ke **Views & UI**? 🚀