public class Display implements Observer{
    @Override
    public void update(String title, String content, String date){
        System.out.println(title + content + date + this);
    }

    public void fly(){
        System.out.println(this + "fly");
    }

    @Override
    public String toString() {
        return getClass().getName();
    }
    
    
}
