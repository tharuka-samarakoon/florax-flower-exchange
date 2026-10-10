# FloraX --- QA Test Cases

## 1. Overview

FloraX is a flower exchange project intended to support flower-trading
workflows. This document provides proposed test cases for reviewing the
system's behaviour.

> **Important:** These are proposed test scenarios. Update actual
> results and statuses only after running each test against the current
> implementation.

## 2. Test Cases

  --------------------------------------------------------------------------------
  Test Case   Scenario       Steps          Expected       Actual      Status
  ID                                        Result         Result      
  ----------- -------------- -------------- -------------- ----------- -----------
  FX-TC-001   View flower    Open the       Available      Not         Not Run
              listings       flower listing listings and   recorded    
                             screen         their relevant             
                                            details are                
                                            displayed                  

  FX-TC-002   Search for a   Search for a   Matching       Not         Not Run
              flower         known flower   listings are   recorded    
                             name           displayed                  

  FX-TC-003   Submit a valid Enter valid    The order is   Not         Not Run
              sell order     order details  accepted and   recorded    
                             and submit     appropriate                
                                            confirmation               
                                            is shown                   

  FX-TC-004   Submit a valid Enter valid    The order is   Not         Not Run
              buy order      order details  accepted and   recorded    
                             and submit     appropriate                
                                            confirmation               
                                            is shown                   

  FX-TC-005   Validate an    Enter a zero,  Invalid price  Not         Not Run
              invalid price  negative, or   is rejected    recorded    
                             otherwise      with a clear               
                             invalid price  validation                 
                                            message                    

  FX-TC-006   Validate an    Enter zero, a  Invalid        Not         Not Run
              invalid        negative       quantity is    recorded    
              quantity       quantity, or   rejected with              
                             invalid text   a clear                    
                                            validation                 
                                            message                    

  FX-TC-007   Check required Leave required Missing        Not         Not Run
              fields         fields empty   information is recorded    
                             and submit     identified and             
                                            the submission             
                                            is rejected                

  FX-TC-008   Match          Create         Orders are     Not         Not Run
              compatible     compatible buy matched when   recorded    
              orders         and sell       the configured             
                             orders         matching rules             
                                            are satisfied              

  FX-TC-009   Prevent        Create orders  Incompatible   Not         Not Run
              incompatible   that do not    orders are not recorded    
              order matching meet matching  matched                    
                             rules                                     

  FX-TC-010   Cancel an      Request        Order status   Not         Not Run
              order          cancellation   is updated     recorded    
                             for a          according to               
                             cancellable    system rules               
                             order                                     

  FX-TC-011   View order     Open an        The current    Not         Not Run
              status         existing order order status   recorded    
                                            is displayed               
                                            accurately                 

  FX-TC-012   View market    Open the       Available      Not         Not Run
              information    market         market         recorded    
                             information or information is             
                             statistics     displayed                  
                             view           consistently               
                                            with the data              
                                            source                     

  FX-TC-013   Search for an  Search for a   An appropriate Not         Not Run
              unknown item   term with no   no-results     recorded    
                             matching       message is                 
                             listing        shown                      

  FX-TC-014   Handle invalid Enter          Input is       Not         Not Run
              input format   unsupported    validated and  recorded    
                             characters or  the system                 
                             an incorrectly does not crash             
                             formatted                                 
                             value                                     

  FX-TC-015   Verify saved   Save a record, Saved data     Not         Not Run
              data           navigate away, remains        recorded    
                             then return or available if               
                             refresh        persistence is             
                                            supported                  
  --------------------------------------------------------------------------------

## 3. Test Execution Summary

Update this section after testing.

-   **Total test cases:** 15
-   **Passed:** To be updated
-   **Failed:** To be updated
-   **Blocked:** To be updated
-   **Not Run:** 15 initially

## 4. Notes

-   Confirm each scenario applies to the features implemented in the
    current FloraX version.
-   If a feature is not implemented, mark the test as `Blocked` or
    `Not Applicable` and explain why.
-   Record the actual result and supporting evidence for every executed
    test.
-   Do not mark a test as passed until the observed result has been
    compared with the expected result.

## 5. Project Context

FloraX was explored through an initial console-based C++ prototype,
followed by collaborative UI redesign and quality assurance activities
during a workshop. Update this description if the repository contains
additional implementations or technologies.
