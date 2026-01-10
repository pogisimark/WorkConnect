# AI-Powered Job Matching Features

## Overview
The WorkConnect system now includes **free AI-powered job matching** that intelligently matches jobs to jobseekers based on their NRSP form data. The AI system uses semantic matching and proximity algorithms - **completely free with no expiration**.

## Key Features

### 1. **Intelligent Location Proximity Matching**
- **Problem Solved:** If a jobseeker prefers "Manila" but a job is in "Makati", the old system wouldn't show it
- **AI Solution:** The system understands that Manila and Makati are nearby (both in Metro Manila) and will recommend the job
- **How it works:**
  - Uses location proximity groups (Metro Manila cities are grouped together)
  - Calculates proximity scores (85% for nearby locations, 100% for exact match)
  - Handles partial matches (e.g., "Metro Manila" contains "Manila")

**Example:**
- Jobseeker preference: "Manila"
- Job location: "Makati"
- Result: ✅ **85% match** - AI detected nearby location

### 2. **"Any" Occupation Handling**
- **Problem Solved:** If a jobseeker enters "any" in preferred occupation, they should still see relevant jobs
- **AI Solution:** The system treats "any" as a match for all occupations with an 80% score
- **How it works:**
  - Detects "any" or "n/a" values in occupation preferences
  - Automatically matches all job types
  - Shows visual indicator (🤖 robot icon) when AI is handling "any"

**Example:**
- Jobseeker preference: "any"
- Job title: "Software Developer"
- Result: ✅ **80% match** - AI matched "any" preference

### 3. **Semantic Occupation Matching**
- **Problem Solved:** Exact word matching misses related jobs (e.g., "Software Developer" vs "Programmer")
- **AI Solution:** Uses occupation groups to understand related job titles
- **How it works:**
  - Groups related occupations (e.g., software developer, programmer, web developer)
  - Matches jobs even if exact words don't match
  - Uses word similarity algorithms

**Example:**
- Jobseeker preference: "Software Developer"
- Job title: "Web Developer"
- Result: ✅ **85% match** - AI detected related occupation

### 4. **Free & No Expiration**
- Uses **local algorithms** (no external API calls needed)
- **No API keys required**
- **No expiration date**
- **No rate limits**
- Works completely offline

## Technical Implementation

### Files Created/Modified:
1. **`Employee/ai_job_matcher.php`** - New AI matching class
2. **`Employee/job_matching_algorithm.php`** - Updated to use AI matcher
3. **`Employee/recommended_jobs.php`** - Updated UI to show AI indicators

### Location Proximity Groups:
```php
'metro_manila' => [
    'manila', 'makati', 'quezon city', 'taguig', 'pasig', 
    'mandaluyong', 'san juan', 'muntinlupa', 'las piñas', 
    'parañaque', 'valenzuela', 'caloocan', 'malabon', 
    'navotas', 'marikina', 'pateros'
]
```

### Occupation Groups:
```php
'software' => ['software developer', 'programmer', 'web developer', ...],
'marketing' => ['marketing', 'digital marketing', 'social media', ...],
'customer_service' => ['customer service', 'call center', 'support', ...],
// ... and more
```

## User Experience

### Visual Indicators:
- 🤖 **Robot icon** appears when AI is handling "any" preferences
- 🟢 **Green badge** for "any" occupations (indicates AI matching)
- 💡 **Tooltip** shows "AI will match all relevant jobs"
- 📍 **"AI detected nearby location"** message for proximity matches

### Example Scenarios:

**Scenario 1: Location Proximity**
- User prefers: "Manila"
- Job location: "Makati"
- Display: "AI detected nearby location" with 85% match

**Scenario 2: "Any" Occupation**
- User prefers: "any"
- Job title: "Marketing Assistant"
- Display: "Any (AI matched)" with 80% match

**Scenario 3: Semantic Matching**
- User prefers: "Software Developer"
- Job title: "Web Developer"
- Display: "85% match" - related occupation detected

## Benefits

1. ✅ **More Job Recommendations** - Shows relevant jobs even with "any" or nearby locations
2. ✅ **Better Accuracy** - Understands context and relationships
3. ✅ **Free Forever** - No API costs, no expiration
4. ✅ **Transparent** - Users see when AI is making matches
5. ✅ **No Setup Required** - Works immediately

## Future Enhancements (Optional)

If you want even more advanced AI matching, you can optionally enable Hugging Face API (still free):
- Uncomment the API code in `ai_job_matcher.php`
- Uses sentence transformers for semantic similarity
- Still free tier, but requires internet connection

## Testing

To test the AI matching:
1. Set preferred occupation to "any" → Should see all jobs
2. Set preferred location to "Manila" → Should see jobs in Makati, Quezon City, etc.
3. Set preferred occupation to "Software Developer" → Should see "Web Developer", "Programmer", etc.

## Support

The AI matching system is fully integrated and works automatically. No configuration needed!
