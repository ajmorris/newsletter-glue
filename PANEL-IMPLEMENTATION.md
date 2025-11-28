# Newsletter Glue: Panel Implementation

## Overview

Newsletter Glue now supports displaying post newsletter settings in either a **Meta Box** (below the editor) or a **Sidebar Panel** (on the right side of the block editor). This gives users flexibility in how they prefer to interact with newsletter settings.

## Features Implemented

### 1. Settings Option
- Added a new setting in **Newsletter Glue Settings > Email Defaults > Editor Settings**
- Users can choose between:
  - **Meta Box (below editor)** - Traditional metabox at the bottom of the post editor
  - **Sidebar Panel (right side)** - Modern panel in the block editor sidebar

### 2. Sidebar Panel Component
Created a comprehensive React-based panel that includes:
- **Subject line** input
- **Preview text** input
- **Audience** selector (dynamically loaded from your ESP)
- **Segment/Tag** selector (loads segments based on selected audience)
- **From name** input
- **From email** input
- **Read on site link** toggle with custom label option
- **Send as newsletter** toggle
- Real-time status indicators for sent/scheduled newsletters
- Connection status check with helpful messages

### 3. REST API Integration
- Registered meta fields for REST API access
- Created custom endpoints:
  - `/newsletterglue/v1/defaults/{post_id}` - Get newsletter defaults and settings
  - `/newsletterglue/v1/segments?audience={id}` - Get segments for an audience
- All data is securely saved using WordPress REST API with proper authentication

### 4. Conditional Display
- Meta box only appears when setting is set to "Meta Box"
- Panel only loads when setting is set to "Sidebar Panel"
- Both options save to the same data structure, ensuring compatibility

### 5. Automatic Sending
- When "Send as newsletter" is toggled ON, the newsletter will be sent when:
  - The post is published (sends immediately)
  - The post is scheduled (sends when published)
- Works identically in both meta box and panel modes
- Send toggle automatically resets after sending to prevent duplicate sends

### 6. Pre-Publish Checks
- Pre-publish panel automatically shows newsletter details before publishing
- Always enabled when using panel mode
- Reads newsletter data from both metabox (DOM) and panel (REST API meta)
- Displays:
  - Subject line
  - Audience name
  - Segment (if selected)
  - Email service provider
  - Confirmation message

## Files Created/Modified

### New Files:
1. **includes/rest-api.php** - REST API endpoints and meta registration
2. **assets/js/gutenberg/newsletter-panel.js** - React panel component
3. **assets/css/newsletter-panel.css** - Panel styling

### Modified Files:
1. **newsletter-glue.php** - Added rest-api.php include
2. **includes/admin/meta-boxes.php** - Added conditional display and robust panel-aware save logic
3. **includes/admin/settings/views/settings-general.php** - Added editor settings option
4. **includes/ajax-functions.php** - Added AJAX handler for settings location
5. **includes/cpt/cpt.php** - Added panel script/style enqueuing and confirmation panel integration
6. **assets/js/gutenberg/confirm-send.js** - Enhanced to read from REST API meta for panel mode

## How to Use

### For Users:
1. Go to **Newsletter Glue > Settings > Email Defaults**
2. Scroll down to **Editor Settings** section
3. Choose your preferred **Settings location**:
   - Select "Meta Box (below editor)" for traditional layout
   - Select "Sidebar Panel (right side)" for modern sidebar panel
4. Click **Save**
5. When editing a post, newsletter settings will appear in your chosen location

### For Panel Users:
1. Open any post in the block editor
2. Look for the **Newsletter Glue** panel in the right sidebar (below Document or Block settings)
3. Fill in your newsletter details:
   - Subject and preview text
   - Select audience and optional segment
   - Customize sender information if needed
   - Toggle "Send as newsletter" when ready
4. Publish or update the post - newsletter will be sent automatically if toggle is ON

## Technical Details

### Data Storage
- All newsletter data is stored in the `_newsletterglue` post meta field
- Data structure remains the same regardless of whether panel or metabox is used
- Future send status stored in `_ngl_future_send` post meta
- Send toggle automatically resets to `0` after newsletter is sent (panel mode only)

### Save Logic
- **Metabox mode**: Traditional form submission with nonce verification
- **Panel mode**: REST API saves meta data, then `save_post` hook reads and processes
- Static array prevents duplicate sends within the same request
- Hook runs at priority 1 to execute early in the save process

### Pre-Publish Integration
- Confirmation panel automatically enabled when using panel mode
- Reads data from multiple sources:
  1. Localized script data (populated from post meta)
  2. REST API meta (for panel mode real-time data)
  3. DOM elements (for metabox mode)
- Fallback chain ensures data is always displayed correctly

### Security
- All REST API endpoints require `manage_newsletterglue` capability
- Nonce verification for metabox submissions
- REST API authentication for panel submissions
- Proper sanitization and validation throughout

### Compatibility
- Works with all existing integrations (Mailchimp, MailerLite, etc.)
- Backward compatible - existing newsletters continue to work
- Can switch between metabox and panel at any time without data loss

## Benefits

### Meta Box (Traditional)
- ✅ All settings visible at once
- ✅ Familiar WordPress interface
- ✅ Good for users who prefer traditional forms
- ✅ Doesn't take up sidebar space

### Sidebar Panel (Modern)
- ✅ Keeps content area clean
- ✅ Always accessible without scrolling
- ✅ Native WordPress block editor experience
- ✅ Real-time updates and validation
- ✅ Modern, streamlined interface

## Support

The implementation follows WordPress coding standards and best practices:
- Uses WordPress REST API
- Proper capability checks
- Secure data handling
- Compatible with WordPress core updates
- Clean, maintainable code

## Future Enhancements

Potential improvements:
- Add inline validation with error messages
- Add test email functionality to panel
- Add preview functionality
- Add bulk send options
- Add scheduling calendar view

