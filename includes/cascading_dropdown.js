/**
 * Cascading Dropdown JavaScript Component
 * Handles dynamic loading of location hierarchy dropdowns
 */

class CascadingDropdown {
    constructor(fieldName, levels) {
        this.fieldName = fieldName;
        this.levels = levels; // Array of level names: ['region', 'province', 'citymun', 'barangay']
        this.dropdowns = {};
        this.apiBase = 'api/locations.php';
        
        this.init();
    }
    
    init() {
        // Create dropdown elements for each level
        this.levels.forEach((level, index) => {
            const selectId = `${this.fieldName}_${level}`;
            const select = document.getElementById(selectId);
            
            if (select) {
                this.dropdowns[level] = {
                    element: select,
                    index: index
                };
                
                // Add change event listener
                select.addEventListener('change', (e) => {
                    this.onDropdownChange(level, e.target.value);
                });
                
                // Disable all dropdowns except the first one initially
                if (index > 0) {
                    select.disabled = true;
                    this.clearDropdown(select);
                }
            }
        });
        
        // Load initial data for the first level
        if (this.levels.length > 0) {
            this.loadData(this.levels[0]);
        }
    }
    
    onDropdownChange(level, value) {
        const currentIndex = this.dropdowns[level].index;
        
        // Clear and disable all subsequent dropdowns
        for (let i = currentIndex + 1; i < this.levels.length; i++) {
            const nextLevel = this.levels[i];
            if (this.dropdowns[nextLevel]) {
                this.clearDropdown(this.dropdowns[nextLevel].element);
                this.dropdowns[nextLevel].element.disabled = true;
            }
        }
        
        // If a value is selected, load data for the next level
        if (value && currentIndex + 1 < this.levels.length) {
            const nextLevel = this.levels[currentIndex + 1];
            this.loadData(nextLevel, level, value);
        }
        
        // Update hidden field with selected values
        this.updateHiddenField();
    }
    
    loadData(level, parentLevel = null, parentValue = null) {
        let url = `${this.apiBase}?action=get_${level}`;
        
        // Add parent parameter based on level
        if (parentLevel && parentValue) {
            if (level === 'provinces') {
                url += `&region_code=${encodeURIComponent(parentValue)}`;
            } else if (level === 'citymun' || level === 'barangays') {
                url += `&parent_id=${encodeURIComponent(parentValue)}`;
            }
        }
        
        // Show loading state
        const dropdown = this.dropdowns[level].element;
        dropdown.innerHTML = '<option value="">Loading...</option>';
        dropdown.disabled = true;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.populateDropdown(level, data.data);
                } else {
                    console.error('Error loading data:', data.message);
                    dropdown.innerHTML = '<option value="">Error loading data</option>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                dropdown.innerHTML = '<option value="">Error loading data</option>';
            });
    }
    
    populateDropdown(level, data) {
        const dropdown = this.dropdowns[level].element;
        
        // Clear existing options
        dropdown.innerHTML = '<option value="">Select an option</option>';
        
        // Add data options
        data.forEach(item => {
            const option = document.createElement('option');
            
            // Set value and text based on level
            switch (level) {
                case 'regions':
                    option.value = item.region_code;
                    option.textContent = item.region_name;
                    break;
                case 'provinces':
                    option.value = item.id;
                    option.textContent = item.province_name;
                    break;
                case 'citymun':
                    option.value = item.id;
                    option.textContent = item.citymun_name;
                    break;
                case 'barangays':
                    option.value = item.id;
                    option.textContent = item.barangay_name;
                    break;
            }
            
            dropdown.appendChild(option);
        });
        
        // Enable the dropdown
        dropdown.disabled = false;
    }
    
    clearDropdown(dropdown) {
        dropdown.innerHTML = '<option value="">Select an option</option>';
    }
    
    updateHiddenField() {
        // Create a JSON object with all selected values
        const selectedValues = {};
        
        this.levels.forEach(level => {
            const dropdown = this.dropdowns[level];
            if (dropdown && dropdown.element.value) {
                selectedValues[level] = {
                    value: dropdown.element.value,
                    text: dropdown.element.options[dropdown.element.selectedIndex].text
                };
            }
        });
        
        // Update hidden field with JSON data
        const hiddenField = document.getElementById(`${this.fieldName}_data`);
        if (hiddenField) {
            hiddenField.value = JSON.stringify(selectedValues);
        }
    }
    
    // Method to set values programmatically (for editing)
    async setValues(values) {
        if (!values) return;
        
        try {
            // Parse JSON if it's a string
            const data = typeof values === 'string' ? JSON.parse(values) : values;
            console.log('Setting cascading dropdown values:', data);
            
            // Set values in sequence: regions -> provinces -> citymun -> barangays
            if (data.regions) {
                await this.setLevelValue('regions', data.regions.value, data.regions.text);
            }
            
            if (data.provinces) {
                await this.setLevelValue('provinces', data.provinces.value, data.provinces.text);
            }
            
            if (data.citymun) {
                await this.setLevelValue('citymun', data.citymun.value, data.citymun.text);
            }
            
            if (data.barangays) {
                await this.setLevelValue('barangays', data.barangays.value, data.barangays.text);
            }
            
            // Update the hidden field
            this.updateHiddenField();
            
        } catch (error) {
            console.error('Error setting cascading dropdown values:', error);
        }
    }
    
    // Helper method to set value for a specific level
    async setLevelValue(level, value, text) {
        const dropdown = this.dropdowns[level];
        if (!dropdown) return;
        
        // Wait for dropdown to be populated if needed
        await this.waitForDropdownReady(level);
        
        // Find and select the option
        const option = Array.from(dropdown.element.options).find(opt => opt.value === value);
        if (option) {
            dropdown.element.value = value;
            console.log(`Set ${level} to: ${text} (${value})`);
            
            // Trigger change event to load next level
            const changeEvent = new Event('change', { bubbles: true });
            dropdown.element.dispatchEvent(changeEvent);
            
            // Wait a bit for the next level to load
            await new Promise(resolve => setTimeout(resolve, 300));
        } else {
            console.warn(`Option not found for ${level}: ${text} (${value})`);
        }
    }
    
    // Helper method to wait for dropdown to be ready
    waitForDropdownReady(level) {
        return new Promise((resolve) => {
            const dropdown = this.dropdowns[level];
            if (!dropdown) {
                resolve();
                return;
            }
            
            const checkReady = () => {
                if (!dropdown.element.disabled && dropdown.element.options.length > 1) {
                    resolve();
                } else {
                    setTimeout(checkReady, 100);
                }
            };
            
            checkReady();
        });
    }
}

// Global function to initialize cascading dropdowns
function initCascadingDropdown(fieldName, levels, initialValues = null) {
    const dropdown = new CascadingDropdown(fieldName, levels);
    
    // Set initial values if provided (for editing)
    if (initialValues) {
        // Wait a bit for the dropdown to initialize, then set values
        setTimeout(() => {
            dropdown.setValues(initialValues);
        }, 1000);
    }
    
    return dropdown;
}