public class Display implements Observer{
    @Override
    public void update(Subject s){
        Publisher p = (Publisher)s;
        System.out.println(p.getTitle() +  p.getContent() + p.getDate());
    }

    public void fly(){
        System.out.println(this + "fly");
    }

    @Override
    public String toString() {
        return getClass().getName();
    }
    
    
}
