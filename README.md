

# BRAND MASTER LOGIC
    SINCE, WE GONNA PUT MULTI BOTTLE SIZE BRAND
    E.G ABSOLUTE 750,ABSOLUTE 1000,ABSOLUTE 500
    SO WE CAN ADD FULLNAME WITH SIZE IN BRAND NAME COLUMN OF BRAND MASTER. BUT IN NAME DONT PUT ML WORD.
    WITH THAT IN REPORTS WE CAN REMOVE NUMBERS AND\



company  - company name,gst, pan 
branch -  company location (will be mapped with company)

## MASTER
CATEGORY (NAME TO DIFFERENTIATE) 
BRAND (NAME AND BOTTLE SIZE DETAILS) /  MAPPED WITH CATEGORY

## AUDIT
# PURCHASE
    COMPANY_ID
    BRANCH_ID
    CATEGORY
    BRAND
    QTY(NO BOTTLE)
    

## SALE
    COMPANY_ID
    BRANCH_ID
    # LIQOUR (BRAND)    
        CATEGORY
        BRAND
        NO_BTL 
        NP_PEG 
        
    # RECIPE (COCKTAILS)
        RECIPE
        GLASS
        
modify sale table (saleType, recipe_code)

## STOCK
    COMPANY_ID
    BRANCH_ID
    CATEGORY
    BRAND
    QTY (ML) 
