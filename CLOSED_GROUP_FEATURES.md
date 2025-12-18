# Closed Group Features & Delete Functionality

## Summary
Implemented restrictions for closed groups and added group deletion functionality.

## Changes Made

### 1. Closed Group Restrictions

When a group is closed, the following restrictions apply:

**❌ Blocked Actions (Not Allowed):**
- Adding new expenses
- Deleting expenses
- Adding meals
- Inviting new members
- Recording market expenses

**✅ Allowed Actions:**
- Viewing expenses (read-only)
- Viewing analytics dashboard
- Viewing settlements
- Generating PDF reports
- Viewing meal calculations (read-only)

### 2. Group Deletion

- Only group creator can delete a group
- Group must be closed before deletion (delete button only appears for closed groups)
- Deletion is permanent and removes all related data (cascade delete):
  - All expenses and expense splits
  - All meals
  - All market expenses
  - All group members
  - All invitations

### 3. Backend Changes

#### New Methods in `Group.php`:
- `isClosed($groupId)` - Check if group is closed
- `delete($groupId, $userId)` - Delete group (creator only)

#### Updated API Endpoints:
- `expenses/create.php` - Checks if group is closed before allowing creation
- `expenses/delete.php` - Checks if group is closed before allowing deletion
- `meals/add.php` - Checks if group is closed before allowing meal entry
- `groups/invite.php` - Checks if group is closed before allowing invitations
- `Meal.php::recordMarketExpense()` - Checks if group is closed

#### New API Endpoint:
- `groups/delete.php` - DELETE endpoint to delete a group

### 4. Frontend Changes

#### `groups/details.html`:
- Shows "Closed" badge when group is closed
- Hides "Add Expense" button for closed groups
- Hides "Invite Member" button for closed groups
- Shows "Delete Group" button (only for creator, only on closed groups)
- "Analytics" button always visible (works for closed groups)

#### `groups.js`:
- Added `deleteGroup(groupId)` method

#### `api.js`:
- Updated `delete()` method to send data in request body (instead of query params)

## Usage

### Closing a Group:
1. Open group details page
2. Click "Close Group" button (only visible to creator)
3. Confirm the action
4. Group is marked as closed, activities are restricted

### Deleting a Group:
1. Close the group first
2. On group details page, "Delete Group" button appears (only for creator)
3. Click "Delete Group"
4. Confirm deletion (warning about permanent action)
5. Group and all related data are permanently deleted
6. User is redirected to groups list

## Database Schema

The database uses CASCADE DELETE, so when a group is deleted:
- All related records in child tables are automatically deleted
- No orphaned records remain

## Testing Checklist

- [ ] Close a group - should block expense creation
- [ ] Close a group - should block meal addition
- [ ] Close a group - should block invitations
- [ ] Close a group - should still allow analytics viewing
- [ ] Close a group - should still allow PDF generation
- [ ] Delete a closed group - should remove all data
- [ ] Try to delete as non-creator - should fail
- [ ] Try to delete open group - delete button should not appear

